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

class SignTreasuryTransaction implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // Strict single attempt before recovery check
    public int $timeout = 45;

    public function __construct(public int $depositId)
    {
        $this->onQueue((string) config('custody.queues.signing', 'signing'));
    }

    public function uniqueId(): string
    {
        return "sign-sweep-{$this->depositId}";
    }

    public function handle(SweepEngine $sweepEngine): void
    {
        $deposit = Deposit::findOrFail($this->depositId);
        $sweepEngine->queueAndExecuteSweep($deposit, false);
    }
}

