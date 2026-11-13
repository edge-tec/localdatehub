<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\Deposit;
use App\Services\Deposit\ConfirmationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateConfirmationCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public int $timeout = 45;
    public array $backoff = [15, 30, 60, 120];

    public function __construct(public int $depositId)
    {
        $this->onQueue((string) config('custody.queues.verification', 'verification'));
    }

    public function handle(ConfirmationService $confirmations): void
    {
        $deposit = Deposit::findOrFail($this->depositId);
        $confirmations->refreshConfirmations($deposit);

        if ($confirmations->hasSufficientConfirmations($deposit->fresh())) {
            VerifyBlockchainTransaction::dispatch($deposit->id)
                ->onQueue((string) config('custody.queues.verification', 'verification'));
        }
    }
}

