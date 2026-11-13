<?php

namespace App\Services\Deposit;

use App\Models\Wallet\BlockchainTransaction;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\WalletAddress;
use App\Services\Support\BaseUnitMath;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DepositDetector
{
    /**
     * Record an observed incoming blockchain transaction idempotently.
     *
     * @param array{
     *     tx_hash: string,
     *     tx_index?: int,
     *     block_height?: int|null,
     *     block_hash?: string|null,
     *     from_address?: string|null,
     *     to_address: string,
     *     contract_address?: string|null,
     *     amount_base_units: string,
     *     confirmations: int,
     *     is_reverted?: bool,
     *     raw_reference?: string|null
     * } $txData
     */
    public function recordObservedDeposit(WalletAddress $walletAddress, array $txData): Deposit
    {
        $amount = BaseUnitMath::normalize((string) ($txData['amount_base_units'] ?? '0'));
        if (!BaseUnitMath::isPositive($amount)) {
            throw new InvalidArgumentException('Observed deposit amount must be strictly positive.');
        }

        return DB::transaction(function () use ($walletAddress, $txData, $amount) {
            $confirmations = max(0, (int) ($txData['confirmations'] ?? 0));
            $isReverted = (bool) ($txData['is_reverted'] ?? false);

            $status = $isReverted
                ? Deposit::STATUS_FAILED
                : ($confirmations > 0 ? Deposit::STATUS_CONFIRMING : Deposit::STATUS_DETECTED);

            $btx = BlockchainTransaction::updateOrCreate(
                [
                    'network_id' => $walletAddress->network_id,
                    'transaction_hash' => trim((string) $txData['tx_hash']),
                    'recipient_address' => trim((string) $txData['to_address']),
                    'asset_id' => $walletAddress->asset_id,
                ],
                [
                    'transaction_index' => (int) ($txData['tx_index'] ?? 0),
                    'block_height' => $txData['block_height'] ?? null,
                    'block_hash' => $txData['block_hash'] ?? null,
                    'sender_address' => $txData['from_address'] ?? null,
                    'amount_base_units' => $amount,
                    'confirmation_count' => $confirmations,
                    'transaction_status' => $status,
                    'raw_transaction_reference' => $txData['raw_reference'] ?? null,
                    'detected_at' => now(),
                ]
            );

            $deposit = Deposit::where('blockchain_transaction_id', $btx->id)->lockForUpdate()->first();
            if ($deposit) {
                if (!in_array($deposit->status, [Deposit::STATUS_CREDITED, Deposit::STATUS_SWEEP_QUEUED, Deposit::STATUS_SWEEPING, Deposit::STATUS_SWEPT], true)) {
                    $deposit->update(['status' => $status]);
                }

                return $deposit->fresh();
            }

            return Deposit::create([
                'user_id' => $walletAddress->user_id,
                'wallet_address_id' => $walletAddress->id,
                'blockchain_transaction_id' => $btx->id,
                'asset_id' => $walletAddress->asset_id,
                'network_id' => $walletAddress->network_id,
                'amount_base_units' => $amount,
                'credited_amount_base_units' => '0',
                'status' => $status,
            ]);
        });
    }
}

