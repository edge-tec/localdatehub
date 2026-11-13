<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\Deposit;
use App\Services\Deposit\DepositCreditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreditVerifiedDeposit implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 30;
    public array $backoff = [5, 15, 30];

    public function __construct(public int $depositId)
    {
        $this->onQueue((string) config('custody.queues.crediting', 'crediting'));
    }

    public function uniqueId(): string
    {
        return "credit-deposit-{$this->depositId}";
    }

    public function handle(DepositCreditService $creditService): void
    {
        $deposit = Deposit::findOrFail($this->depositId);
        $creditService->creditVerifiedDeposit($deposit, true);
    }
}

