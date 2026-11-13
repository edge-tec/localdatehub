<?php

namespace App\Services\Deposit;

use App\Models\Wallet\BlockchainTransaction;
use App\Models\Wallet\Deposit;
use App\Services\Blockchain\BlockchainMonitor;

class ConfirmationService
{
    public function __construct(
        protected BlockchainMonitor $monitor
    ) {}

    public function refreshConfirmations(Deposit $deposit): int
    {
        $btx = $deposit->blockchainTransaction;
        $network = $deposit->network;
        $asset = $deposit->asset;

        if (!$btx || !$network || !$asset) {
            return 0;
        }

        $adapter = $this->monitor->adapterFor($network);
        $chainData = $adapter->verifyTransaction($network, $asset, $btx->transaction_hash);

        if (!$chainData['exists']) {
            return 0;
        }

        $confs = (int) $chainData['confirmations'];
        $btx->update([
            'confirmation_count' => $confs,
            'block_height' => $chainData['block_height'] ?? $btx->block_height,
            'block_hash' => $chainData['block_hash'] ?? $btx->block_hash,
        ]);

        if ($chainData['is_reverted']) {
            $btx->update(['transaction_status' => Deposit::STATUS_FAILED]);
            $deposit->update(['status' => Deposit::STATUS_FAILED]);
            return $confs;
        }

        if ($confs > 0 && $deposit->status === Deposit::STATUS_DETECTED) {
            $deposit->update(['status' => Deposit::STATUS_CONFIRMING]);
        }

        return $confs;
    }

    public function hasSufficientConfirmations(Deposit $deposit): bool
    {
        $btx = $deposit->blockchainTransaction;
        $threshold = (int) ($deposit->network?->confirmation_threshold ?? 6);

        return $btx !== null && $btx->confirmation_count >= $threshold;
    }
}

