<?php

namespace App\Services\Treasury;

use App\Models\Wallet\RecoveryQueueItem;
use App\Models\Wallet\SweepTransaction;

class SweepRecoveryService
{
    public function __construct(
        protected SweepEngine $sweepEngine
    ) {}

    public function processItem(RecoveryQueueItem $item): bool
    {
        if ($item->operation_type !== 'SWEEP') {
            return false;
        }

        $sweep = SweepTransaction::with('deposit')->find($item->reference_id);
        if (!$sweep || !$sweep->deposit) {
            $item->update(['status' => 'FAILED']);
            return false;
        }

        $updated = $this->sweepEngine->queueAndExecuteSweep($sweep->deposit, true);

        if (in_array($updated->status, ['SWEEPING', 'SWEPT'], true)) {
            $item->update(['status' => 'RESOLVED']);
            return true;
        }

        $retries = $item->retry_count + 1;
        $item->update([
            'retry_count' => $retries,
            'failure_code' => $updated->last_error_code ?? $item->failure_code,
            'next_retry_at' => now()->addMinutes(min(60, 2 ** min(5, $retries))),
            'status' => $retries >= 10 ? 'ESCALATED' : 'PENDING',
        ]);

        return false;
    }
}

