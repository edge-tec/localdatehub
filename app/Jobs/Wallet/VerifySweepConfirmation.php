<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\SweepTransaction;
use App\Services\Treasury\SweepEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifySweepConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public int $timeout = 45;
    public array $backoff = [15, 30, 60, 120];

    public function __construct(public int $sweepId)
    {
        $this->onQueue((string) config('custody.queues.sweeping', 'sweeping'));
    }

    public function handle(SweepEngine $sweepEngine): void
    {
        $sweep = SweepTransaction::findOrFail($this->sweepId);
        $sweepEngine->verifyBroadcastedSweep($sweep);
    }
}

