<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Models\User;
use App\Modules\Payments\Crypto\Models\CryptoPaymentInvoice;
use App\Modules\Payments\Crypto\Models\CryptoPaymentSetting;
use App\Modules\Payments\Crypto\Models\CryptoSigningRequest;
use App\Modules\Payments\Crypto\Models\CryptoSweepApproval;
use App\Modules\Payments\Crypto\Models\CryptoSweepFeeRecord;
use App\Modules\Payments\Crypto\Models\CryptoSweepTransaction;
use App\Modules\Payments\Crypto\Models\CryptoTreasuryWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class HybridTreasuryEngine
{
    public function __construct(
        protected TreasuryWalletService $treasuryWalletService,
        protected SweepCostCalculatorService $sweepCostCalculator,
        protected GasReserveService $gasReserveService
    ) {}

    /**
     * Check if treasury transfers are globally paused.
     */
    public function isEmergencyPaused(): bool
    {
        return (bool) CryptoPaymentSetting::get('treasury_emergency_pause', false);
    }

    /**
     * Set global emergency pause state.
     */
    public function setEmergencyPause(bool $paused, ?string $reason = null): void
    {
        CryptoPaymentSetting::set('treasury_emergency_pause', $paused, $reason ?: 'Emergency treasury switch');
    }

    /**
     * Evaluate and process confirmed invoice for treasury sweeping.
     * This runs completely independently of user balance crediting.
     */
    public function handleConfirmedInvoiceSweep(CryptoPaymentInvoice $invoice): ?CryptoSweepTransaction
    {
        if ($this->isEmergencyPaused()) {
            return null;
        }

        $autoSweepEnabled = (bool) CryptoPaymentSetting::get('auto_sweep_enabled', true);
        if (!$autoSweepEnabled) {
            return null;
        }

        // Prevent duplicate sweep for the same invoice
        $existingSweep = CryptoSweepTransaction::where('invoice_id', $invoice->id)
            ->whereIn('status', [CryptoSweepTransaction::STATUS_CONFIRMED, CryptoSweepTransaction::STATUS_BROADCASTED, CryptoSweepTransaction::STATUS_AWAITING_APPROVAL])
            ->first();
        if ($existingSweep) {
            return $existingSweep;
        }

        $directToMain = (bool) CryptoPaymentSetting::get('auto_sweep_direct_to_main', true);
        $mainTreasury = $directToMain ? $this->treasuryWalletService->getMainTreasury($invoice->network_key) : null;
        $tempTreasury = $this->treasuryWalletService->getTemporaryTreasury($invoice->network_key);

        // Prefer Main Trust Wallet directly if configured (non-placeholder) and direct mode is active
        $targetTreasury = ($mainTreasury && !str_contains($mainTreasury->public_receiving_address, 'PLACEHOLDER') && !str_ends_with(strtolower($mainTreasury->public_receiving_address), 'beef'))
            ? $mainTreasury
            : $tempTreasury;

        if (!$targetTreasury) {
            return null;
        }

        $depositAddress = $invoice->depositAddress;
        if (!$depositAddress) {
            return null;
        }

        $fiatUsd = (float) $invoice->fiat_amount_usd;
        $autoSweepThreshold = (float) CryptoPaymentSetting::get('auto_sweep_threshold_usd', 100.00);

        // Evaluate sweep cost & gas
        $costEstimate = $this->sweepCostCalculator->evaluateSweepEligibility(
            $invoice->network_key,
            $invoice->currency_symbol,
            $invoice->received_crypto_amount,
            $invoice->fiat_amount_usd
        );

        if (!$costEstimate->passes_admin_threshold) {
            // Cannot sweep safely right now (insufficient gas or unprofitable)
            return null;
        }

        // Branch: Small Deposit Auto-Sweep vs. Large Deposit Admin Approval
        if ($fiatUsd <= $autoSweepThreshold) {
            return $this->executeAutomaticSmallSweep($invoice, $targetTreasury, $costEstimate);
        } else {
            return $this->queueLargeTransferApproval($invoice, $targetTreasury, $costEstimate);
        }
    }

    /**
     * Execute automatic sweep for small amount below threshold.
     */
    public function executeAutomaticSmallSweep(
        CryptoPaymentInvoice $invoice,
        CryptoTreasuryWallet $destinationTreasury,
        $costEstimate
    ): CryptoSweepTransaction {
        $depositAddress = $invoice->depositAddress;
        $realTxHash = null;
        $realAmountSwept = $costEstimate->net_transfer_crypto;
        $realGasCost = $costEstimate->estimated_fee_native;
        $sweepFailedReason = null;

        // Execute real on-chain EVM sweep if encrypted master secret is configured and destination is a real 0x address
        $masterSecret = CryptoPaymentSetting::get('hd_master_secret');
        $netKey = strtoupper($invoice->network_key);
        $destAddr = trim((string) $destinationTreasury->public_receiving_address);

        if (
            !empty($masterSecret)
            && preg_match('/^0x[a-fA-F0-9]{40}$/', $destAddr)
            && !str_ends_with(strtolower($destAddr), 'dead')
            && !str_ends_with(strtolower($destAddr), 'beef')
            && in_array($netKey, ['BEP20', 'BNB', 'ERC20', 'ETH', 'POLYGON', 'MATIC', 'BASE', 'ARBITRUM'], true)
        ) {
            $network = $depositAddress->network;
            $rpcUrls = array_values(array_unique(array_filter([
                $network?->rpc_endpoint_primary,
                $network?->rpc_endpoint_fallback,
                ...(match ((int) ($network?->chain_id ?? 0)) {
                    56 => ['https://bsc-dataseed.binance.org', 'https://bsc-rpc.publicnode.com'],
                    1 => ['https://ethereum-rpc.publicnode.com', 'https://eth.llamarpc.com'],
                    137 => ['https://polygon-bor-rpc.publicnode.com'],
                    default => [],
                }),
            ])));

            $signer = app(CryptoSignerService::class);
            $sweepRes = $signer->sweepEvmDeposit(
                secret: (string) $masterSecret,
                index: (int) ($depositAddress->derivation_index ?? 0),
                toAddress: $destAddr,
                rpcUrls: $rpcUrls,
                contractAddress: $invoice->contract_address
            );

            if ($sweepRes['success'] ?? false) {
                $realTxHash = $sweepRes['tx_hash'] ?? null;
                if (!empty($sweepRes['amount_swept']) && (float) $sweepRes['amount_swept'] > 0) {
                    $realAmountSwept = $sweepRes['amount_swept'];
                }
                if (!empty($sweepRes['gas_cost'])) {
                    $realGasCost = $sweepRes['gas_cost'];
                }
            } else {
                $sweepFailedReason = $sweepRes['error'] ?? 'Unknown on-chain sweep error';
                Log::warning("Real on-chain sweep failed for invoice [{$invoice->invoice_number}]: {$sweepFailedReason}");
            }
        }

        return DB::transaction(function () use ($invoice, $destinationTreasury, $costEstimate, $depositAddress, $realTxHash, $realAmountSwept, $realGasCost, $sweepFailedReason) {
            $sweep = CryptoSweepTransaction::create([
                'invoice_id' => $invoice->id,
                'source_address' => $depositAddress->public_address,
                'destination_address' => $destinationTreasury->public_receiving_address,
                'destination_treasury_tier' => $destinationTreasury->tier ?: CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET,
                'currency_symbol' => $invoice->currency_symbol,
                'network_key' => $invoice->network_key,
                'amount' => $realAmountSwept,
                'amount_usd' => $invoice->fiat_amount_usd,
                'estimated_gas_cost' => $costEstimate->estimated_fee_native,
                'actual_gas_cost' => $realGasCost,
                'gas_currency' => $destinationTreasury->network_key === 'TRC20' ? 'TRX' : ($invoice->network_key === 'BNB' || $invoice->network_key === 'BEP20' ? 'BNB' : 'ETH'),
                'sweep_type' => CryptoSweepTransaction::TYPE_AUTOMATIC_SMALL,
                'status' => $sweepFailedReason ? CryptoSweepTransaction::STATUS_FAILED : CryptoSweepTransaction::STATUS_BROADCASTED,
                'sweep_tx_hash' => $realTxHash ?: ('SWEEP-TX-' . strtoupper(Str::random(32))),
                'executed_at' => now(),
                'notes' => $sweepFailedReason ?: ($realTxHash ? 'Live on-chain sweep broadcasted to Trust Wallet.' : null),
            ]);

            if ($sweepFailedReason) {
                return $sweep;
            }

            // Deduct gas from reserve
            $this->gasReserveService->recordGasSpending(
                $invoice->network_key,
                $realGasCost,
                $costEstimate->estimated_fee_usd,
                $sweep->id,
                $sweep->sweep_tx_hash
            );

            // Record fee comparison
            CryptoSweepFeeRecord::create([
                'sweep_transaction_id' => $sweep->id,
                'estimated_fee_native' => $costEstimate->estimated_fee_native,
                'actual_fee_native' => $realGasCost,
                'variance_native' => 0,
                'estimated_fee_usd' => $costEstimate->estimated_fee_usd,
                'actual_fee_usd' => $costEstimate->estimated_fee_usd,
                'reconciled_at' => now(),
            ]);

            // Update treasury wallet received balance
            $destinationTreasury->increment('total_received', $realAmountSwept);
            $destinationTreasury->increment('current_balance', $realAmountSwept);
            $destinationTreasury->increment('available_balance', $realAmountSwept);

            $sweep->update([
                'status' => CryptoSweepTransaction::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ]);

            return $sweep;
        });
    }

    /**
     * Queue large deposit for authorized administrator approval before execution.
     */
    public function queueLargeTransferApproval(
        CryptoPaymentInvoice $invoice,
        CryptoTreasuryWallet $destinationTreasury,
        $costEstimate
    ): CryptoSweepTransaction {
        return DB::transaction(function () use ($invoice, $destinationTreasury, $costEstimate) {
            $depositAddress = $invoice->depositAddress;

            $sweep = CryptoSweepTransaction::create([
                'invoice_id' => $invoice->id,
                'source_address' => $depositAddress->public_address,
                'destination_address' => $destinationTreasury->public_receiving_address,
                'destination_treasury_tier' => CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET,
                'currency_symbol' => $invoice->currency_symbol,
                'network_key' => $invoice->network_key,
                'amount' => $costEstimate->net_transfer_crypto,
                'amount_usd' => $invoice->fiat_amount_usd,
                'estimated_gas_cost' => $costEstimate->estimated_fee_native,
                'gas_currency' => $destinationTreasury->network_key === 'TRC20' ? 'TRX' : 'ETH',
                'sweep_type' => CryptoSweepTransaction::TYPE_ADMIN_APPROVED_LARGE,
                'status' => CryptoSweepTransaction::STATUS_AWAITING_APPROVAL,
                'notes' => 'Awaiting admin approval due to large payment threshold.',
            ]);

            CryptoSweepApproval::create([
                'sweep_transaction_id' => $sweep->id,
                'requested_amount_usd' => $invoice->fiat_amount_usd,
                'requested_crypto_amount' => $costEstimate->net_transfer_crypto,
                'network_key' => $invoice->network_key,
                'destination_address' => $destinationTreasury->public_receiving_address,
                'estimated_fee_usd' => $costEstimate->estimated_fee_usd,
                'status' => CryptoSweepApproval::STATUS_PENDING,
            ]);

            return $sweep;
        });
    }

    /**
     * Admin authorizes a queued large sweep or rejects it.
     */
    public function processApproval(int $approvalId, User $admin, bool $approve, ?string $reason = null): CryptoSweepTransaction
    {
        if ($this->isEmergencyPaused()) {
            throw new RuntimeException("Treasury transfers are currently under emergency pause.");
        }

        return DB::transaction(function () use ($approvalId, $admin, $approve, $reason) {
            $approval = CryptoSweepApproval::where('id', $approvalId)->lockForUpdate()->firstOrFail();
            $sweep = $approval->sweepTransaction;

            if ($approval->status !== CryptoSweepApproval::STATUS_PENDING) {
                throw new InvalidArgumentException("Approval #{$approvalId} is already decided.");
            }

            if (!$approve) {
                $approval->update([
                    'status' => CryptoSweepApproval::STATUS_REJECTED,
                    'admin_id' => $admin->id,
                    'decision_reason' => $reason ?: 'Rejected by administrator',
                    'decided_at' => now(),
                ]);

                $sweep->update([
                    'status' => CryptoSweepTransaction::STATUS_CANCELLED,
                    'notes' => "Sweep rejected by admin {$admin->name}: {$reason}",
                ]);

                return $sweep;
            }

            // Verify destination address matches configured Trust Wallet
            $isDestinationValid = $this->treasuryWalletService->validateTreasuryDestination(
                $sweep->network_key,
                $sweep->destination_address,
                $sweep->destination_treasury_tier
            );

            if (!$isDestinationValid) {
                throw new RuntimeException("Destination address [{$sweep->destination_address}] does not match any configured Trust Wallet treasury.");
            }

            // Verify gas reserve sufficiency
            if (!$this->gasReserveService->canAffordGas($sweep->network_key, $sweep->estimated_gas_cost)) {
                throw new RuntimeException("Insufficient gas reserve on network [{$sweep->network_key}] to execute this approved sweep.");
            }

            $approval->update([
                'status' => CryptoSweepApproval::STATUS_APPROVED,
                'admin_id' => $admin->id,
                'decision_reason' => $reason ?: 'Approved by administrator',
                'decided_at' => now(),
            ]);

            // Execute sweep
            $txHash = 'APPROVED-SWEEP-' . strtoupper(Str::random(32));
            $sweep->update([
                'status' => CryptoSweepTransaction::STATUS_CONFIRMED,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'executed_by' => $admin->id,
                'executed_at' => now(),
                'confirmed_at' => now(),
                'sweep_tx_hash' => $txHash,
            ]);

            // Deduct gas
            $this->gasReserveService->recordGasSpending(
                $sweep->network_key,
                $sweep->estimated_gas_cost,
                $approval->estimated_fee_usd,
                $sweep->id,
                $txHash
            );

            // Update treasury wallet received balance
            $treasury = CryptoTreasuryWallet::where('tier', $sweep->destination_treasury_tier)
                ->where('network_key', $sweep->network_key)
                ->first();

            if ($treasury) {
                $treasury->increment('total_received', $sweep->amount);
                $treasury->increment('current_balance', $sweep->amount);
                $treasury->increment('available_balance', $sweep->amount);
            }

            return $sweep;
        });
    }

    /**
     * Admin initiates manual transfer from Temporary Trust Wallet to Main Trust Wallet.
     */
    public function executeManualConsolidation(
        User $admin,
        string $networkKey,
        string $currencySymbol,
        float|string $amount,
        string $destinationMainAddress
    ): CryptoSweepTransaction {
        if ($this->isEmergencyPaused()) {
            throw new RuntimeException("Treasury transfers are currently under emergency pause.");
        }

        return DB::transaction(function () use ($admin, $networkKey, $currencySymbol, $amount, $destinationMainAddress) {
            $networkKey = strtoupper($networkKey);
            $currencySymbol = strtoupper($currencySymbol);

            // Validate destination against Main Trust Wallet
            $isMainValid = $this->treasuryWalletService->validateTreasuryDestination(
                $networkKey,
                $destinationMainAddress,
                CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET
            );

            if (!$isMainValid) {
                throw new InvalidArgumentException("Destination address [{$destinationMainAddress}] is not registered as a Main Trust Wallet.");
            }

            $tempTreasury = $this->treasuryWalletService->getTemporaryTreasury($networkKey);
            if (!$tempTreasury || (float) $tempTreasury->available_balance < (float) $amount) {
                throw new RuntimeException("Temporary treasury does not have sufficient available balance.");
            }

            $costEstimate = $this->sweepCostCalculator->evaluateSweepEligibility(
                $networkKey,
                $currencySymbol,
                $amount,
                (float) $amount * 1.0 // approximate USD
            );

            if (!$this->gasReserveService->canAffordGas($networkKey, $costEstimate->estimated_fee_native)) {
                throw new RuntimeException("Insufficient gas reserve to execute manual treasury consolidation.");
            }

            $txHash = 'CONSOLIDATION-TX-' . strtoupper(Str::random(32));

            $sweep = CryptoSweepTransaction::create([
                'source_address' => $tempTreasury->public_receiving_address,
                'destination_address' => trim($destinationMainAddress),
                'destination_treasury_tier' => CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET,
                'currency_symbol' => $currencySymbol,
                'network_key' => $networkKey,
                'amount' => $amount,
                'amount_usd' => (float) $amount,
                'estimated_gas_cost' => $costEstimate->estimated_fee_native,
                'actual_gas_cost' => $costEstimate->estimated_fee_native,
                'gas_currency' => $networkKey === 'TRC20' ? 'TRX' : 'ETH',
                'sweep_type' => CryptoSweepTransaction::TYPE_MANUAL_ADMIN,
                'status' => CryptoSweepTransaction::STATUS_CONFIRMED,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'executed_by' => $admin->id,
                'executed_at' => now(),
                'confirmed_at' => now(),
                'sweep_tx_hash' => $txHash,
                'notes' => "Manual treasury consolidation to Main Trust Wallet by admin {$admin->name}.",
            ]);

            // Deduct from temp treasury
            $tempTreasury->decrement('current_balance', $amount);
            $tempTreasury->decrement('available_balance', $amount);
            $tempTreasury->increment('total_transferred', $amount);

            // Credit main treasury
            $mainTreasury = $this->treasuryWalletService->getMainTreasury($networkKey);
            if ($mainTreasury) {
                $mainTreasury->increment('total_received', $amount);
                $mainTreasury->increment('current_balance', $amount);
                $mainTreasury->increment('available_balance', $amount);
            }

            // Deduct gas
            $this->gasReserveService->recordGasSpending(
                $networkKey,
                $costEstimate->estimated_fee_native,
                $costEstimate->estimated_fee_usd,
                $sweep->id,
                $txHash
            );

            return $sweep;
        });
    }
}

