<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\RecoveryQueueItem;
use App\Services\Treasury\SweepRecoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecoverFailedSweep implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(public int $recoveryItemId)
    {
        $this->onQueue((string) config('custody.queues.recovery', 'recovery'));
    }

    public function uniqueId(): string
    {
        return "recover-item-{$this->recoveryItemId}";
    }

    public function handle(SweepRecoveryService $recovery): void
    {
        $item = RecoveryQueueItem::findOrFail($this->recoveryItemId);
        $recovery->processItem($item);
    }
}

