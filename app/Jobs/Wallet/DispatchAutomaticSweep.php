<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\Deposit;
use App\Services\Treasury\SweepEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchAutomaticSweep implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [30, 60, 120];

    public function __construct(public int $depositId)
    {
        $this->onQueue((string) config('custody.queues.sweeping', 'sweeping'));
    }

    public function uniqueId(): string
    {
        return "sweep-deposit-{$this->depositId}";
    }

    public function handle(SweepEngine $sweepEngine): void
    {
        $deposit = Deposit::findOrFail($this->depositId);
        $sweepEngine->queueAndExecuteSweep($deposit, true);
    }
}

