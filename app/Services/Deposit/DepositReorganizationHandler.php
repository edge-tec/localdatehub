<?php

namespace App\Services\Deposit;

use App\Models\Wallet\BlockchainTransaction;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\RecoveryQueueItem;
use App\Models\Wallet\WalletAuditLog;
use Illuminate\Support\Facades\DB;

class DepositReorganizationHandler
{
    public function handleReorg(BlockchainTransaction $btx, string $reason = 'BLOCK_HASH_MISMATCH'): void
    {
        DB::transaction(function () use ($btx, $reason) {
            $lockedBtx = BlockchainTransaction::where('id', $btx->id)->lockForUpdate()->firstOrFail();
            $lockedBtx->update(['transaction_status' => Deposit::STATUS_REORGED]);

            $deposit = Deposit::where('blockchain_transaction_id', $lockedBtx->id)->lockForUpdate()->first();
            if ($deposit) {
                $wasCredited = $deposit->credited_at !== null;
                $deposit->update(['status' => Deposit::STATUS_REORGED]);

                RecoveryQueueItem::updateOrCreate(
                    ['operation_type' => 'REORG_RECONCILE', 'reference_id' => $deposit->id],
                    [
                        'failure_code' => $wasCredited ? 'REORG_AFTER_CREDIT' : $reason,
                        'status' => 'PENDING',
                        'next_retry_at' => now()->addMinute(),
                    ]
                );

                WalletAuditLog::create([
                    'actor_id' => null,
                    'action' => 'BLOCKCHAIN_REORG_DETECTED',
                    'resource_type' => 'deposit',
                    'resource_id' => (string) $deposit->id,
                    'metadata' => [
                        'tx_hash' => $lockedBtx->transaction_hash,
                        'was_credited' => $wasCredited,
                        'reason' => $reason,
                    ],
                    'created_at' => now(),
                ]);
            }
        });
    }
}

