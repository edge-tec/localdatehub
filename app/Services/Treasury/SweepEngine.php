<?php

namespace App\Services\Treasury;

use App\Events\TreasurySweepUpdated;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\RecoveryQueueItem;
use App\Models\Wallet\SigningRequest;
use App\Models\Wallet\SweepTransaction;
use App\Services\Blockchain\BlockchainMonitor;
use App\Services\Security\SigningGateway;
use App\Services\Support\BaseUnitMath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SweepEngine
{
    public function __construct(
        protected TreasuryService $treasuryService,
        protected GasManager $gasManager,
        protected SigningGateway $signingGateway,
        protected BlockchainMonitor $monitor
    ) {}

    public function queueAndExecuteSweep(Deposit $deposit, bool $broadcastLive = true): SweepTransaction
    {
        $deposit->loadMissing(['network', 'asset', 'walletAddress']);
        $network = $deposit->network;
        $asset = $deposit->asset;
        $walletAddress = $deposit->walletAddress;

        if (!$network || !$asset || !$walletAddress) {
            throw new InvalidArgumentException("Deposit #{$deposit->id} missing network/asset/address.");
        }

        if (!in_array($deposit->status, [
            Deposit::STATUS_CREDITED,
            Deposit::STATUS_SWEEP_QUEUED,
            Deposit::STATUS_RECOVERY_REQUIRED,
        ], true)) {
            throw new InvalidArgumentException("Deposit #{$deposit->id} must be CREDITED before sweeping (current: {$deposit->status}).");
        }

        $treasury = $this->treasuryService->resolveAllowlistedDestination($network, $asset);
        $estimatedFee = $this->gasManager->estimateRequiredFeeBaseUnits($network, $asset);

        $sweep = DB::transaction(function () use ($deposit, $treasury, $network, $estimatedFee) {
            $existing = SweepTransaction::where('deposit_id', $deposit->id)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $deposit->update(['status' => Deposit::STATUS_SWEEP_QUEUED]);

            return SweepTransaction::create([
                'deposit_id' => $deposit->id,
                'treasury_wallet_id' => $treasury->id,
                'network_id' => $network->id,
                'amount_base_units' => $deposit->amount_base_units,
                'network_fee_base_units' => $estimatedFee,
                'status' => Deposit::STATUS_SWEEP_QUEUED,
                'attempt_count' => 0,
            ]);
        });

        // Never blindly retry if a transaction hash already exists — check status first
        if ($sweep->transaction_hash !== null) {
            return $this->verifyBroadcastedSweep($sweep);
        }

        // Check gas reserve before signing/broadcasting
        if (!$this->gasManager->hasSufficientGasReserve($network, $estimatedFee)) {
            return $this->sendToRecovery($deposit, $sweep, 'INSUFFICIENT_GAS_RESERVE');
        }

        $netTransfer = $asset->asset_type === 'native'
            ? BaseUnitMath::sub($deposit->amount_base_units, $estimatedFee)
            : $deposit->amount_base_units;

        if (!BaseUnitMath::isPositive($netTransfer)) {
            return $this->sendToRecovery($deposit, $sweep, 'AMOUNT_BELOW_SWEEP_FEE');
        }

        $minSweep = (string) config('custody.policies.min_sweep_threshold_base_units', '1');
        if (BaseUnitMath::comp($netTransfer, $minSweep) < 0) {
            return $this->sendToRecovery($deposit, $sweep, 'BELOW_MINIMUM_SWEEP_THRESHOLD');
        }

        // Daily sweep limit check
        $dailyMax = (string) config('custody.policies.max_daily_sweep_base_units', '0');
        if (BaseUnitMath::isPositive($dailyMax)) {
            $todayTotal = '0';
            foreach (SweepTransaction::where('network_id', $network->id)->whereDate('created_at', today())->whereIn('status', [Deposit::STATUS_SWEEPING, Deposit::STATUS_SWEPT])->get() as $s) {
                $todayTotal = BaseUnitMath::add($todayTotal, (string) $s->amount_base_units);
            }
            if (BaseUnitMath::comp(BaseUnitMath::add($todayTotal, $netTransfer), $dailyMax) > 0) {
                return $this->sendToRecovery($deposit, $sweep, 'DAILY_SWEEP_LIMIT_EXCEEDED');
            }
        }

        $sponsoredAmount = '0';
        if ($asset->asset_type !== 'native' && $broadcastLive) {
            try {
                $sponsorTx = app(GasSponsorshipManager::class)->ensureGasForTokenSweep($deposit, $estimatedFee, $broadcastLive);
                if ($sponsorTx) {
                    $sponsoredAmount = $sponsorTx->amount_base_units;
                }
            } catch (\Throwable $e) {
                return $this->sendToRecovery($deposit, $sweep, 'GAS_SPONSORSHIP_FAILED');
            }
        }

        try {
            $signed = $this->signingGateway->signTreasurySweep(
                network: $network,
                destinationAddress: $treasury->wallet_address,
                amountBaseUnits: $netTransfer,
                signingPayload: [
                    'chain_id' => $network->chain_id,
                    'from_address' => $walletAddress->address,
                    'derivation_path' => $walletAddress->derivation_path,
                    'derivation_index' => $walletAddress->derivation_index,
                    'contract_address' => $asset->contract_address,
                    'estimated_fee_base_units' => $estimatedFee,
                ]
            );

            SigningRequest::create([
                'sweep_transaction_id' => $sweep->id,
                'network_id' => $network->id,
                'request_uuid' => (string) ($signed['custody_request_id'] ?? Str::uuid()),
                'intent_type' => 'SWEEP',
                'from_address' => $walletAddress->address,
                'to_address' => $treasury->wallet_address,
                'amount_base_units' => $netTransfer,
                'custody_provider' => $this->signingGateway->provider()->providerKey(),
                'status' => 'SIGNED',
            ]);

            $txHash = $signed['tx_hash'];
            if ($broadcastLive && empty($txHash) && !empty($signed['signed_tx'])) {
                $txHash = $this->monitor->adapterFor($network)->broadcastSignedTransaction($network, $signed['signed_tx']);
            }

            $explorerUrl = (!empty($network->explorer_base_url) && !empty($txHash))
                ? rtrim($network->explorer_base_url, '/') . '/tx/' . $txHash
                : null;

            $sweep->update([
                'transaction_hash' => $txHash,
                'explorer_url' => $explorerUrl,
                'amount_base_units' => $netTransfer,
                'network_fee_base_units' => $estimatedFee,
                'gas_sponsored_base_units' => $sponsoredAmount,
                'status' => Deposit::STATUS_SWEEPING,
                'attempt_count' => $sweep->attempt_count + 1,
                'broadcast_at' => now(),
                'last_error_code' => null,
            ]);

            $deposit->update(['status' => Deposit::STATUS_SWEEPING]);
            $this->gasManager->recordGasConsumption($network, $estimatedFee);
            event(new TreasurySweepUpdated($sweep->fresh()));

            return $sweep->fresh();
        } catch (\Throwable $e) {
            return $this->sendToRecovery($deposit, $sweep, 'SWEEP_SIGN_OR_BROADCAST_FAILED');
        }
    }

    public function verifyBroadcastedSweep(SweepTransaction $sweep, bool $forceConfirmed = false): SweepTransaction
    {
        if ($sweep->status === Deposit::STATUS_SWEPT) {
            return $sweep;
        }

        $sweep->loadMissing(['deposit', 'network', 'deposit.asset']);
        $network = $sweep->network;
        $asset = $sweep->deposit?->asset;

        if ($forceConfirmed) {
            $sweep->update([
                'status' => Deposit::STATUS_SWEPT,
                'confirmed_at' => now(),
            ]);
            $sweep->deposit?->update(['status' => Deposit::STATUS_SWEPT]);
            event(new TreasurySweepUpdated($sweep->fresh()));

            return $sweep->fresh();
        }

        if ($network && $asset && $sweep->transaction_hash) {
            $check = $this->monitor->adapterFor($network)->verifyTransaction($network, $asset, $sweep->transaction_hash);
            if ($check['exists'] && !$check['is_reverted'] && $check['confirmations'] >= 1) {
                $sweep->update([
                    'status' => Deposit::STATUS_SWEPT,
                    'confirmed_at' => now(),
                ]);
                $sweep->deposit?->update(['status' => Deposit::STATUS_SWEPT]);
                event(new TreasurySweepUpdated($sweep->fresh()));
            }
        }

        return $sweep->fresh();
    }

    protected function sendToRecovery(Deposit $deposit, SweepTransaction $sweep, string $errorCode): SweepTransaction
    {
        $sweep->update([
            'status' => Deposit::STATUS_RECOVERY_REQUIRED,
            'attempt_count' => $sweep->attempt_count + 1,
            'last_error_code' => $errorCode,
        ]);

        // Deposit stays credited to the user; only sweep state requires recovery
        if ($deposit->credited_at === null) {
            $deposit->update(['status' => Deposit::STATUS_RECOVERY_REQUIRED]);
        }

        RecoveryQueueItem::updateOrCreate(
            ['operation_type' => 'SWEEP', 'reference_id' => $sweep->id],
            [
                'failure_code' => $errorCode,
                'retry_count' => $sweep->attempt_count,
                'next_retry_at' => now()->addMinutes(min(60, 2 ** min(5, $sweep->attempt_count))),
                'status' => 'PENDING',
            ]
        );

        event(new TreasurySweepUpdated($sweep->fresh()));

        return $sweep->fresh();
    }
}

