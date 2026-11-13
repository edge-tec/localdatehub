<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\Deposit;
use App\Services\Deposit\ConfirmationService;
use App\Services\Deposit\DepositVerifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyBlockchainTransaction implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 60;
    public array $backoff = [15, 30, 60, 120];

    public function __construct(
        public int $depositId,
        public ?string $observedContract = null
    ) {
        $this->onQueue((string) config('custody.queues.verification', 'verification'));
    }

    public function uniqueId(): string
    {
        return "verify-deposit-{$this->depositId}";
    }

    public function handle(ConfirmationService $confirmations, DepositVerifier $verifier): void
    {
        $deposit = Deposit::findOrFail($this->depositId);
        $confirmations->refreshConfirmations($deposit);

        if (!$confirmations->hasSufficientConfirmations($deposit->fresh())) {
            UpdateConfirmationCount::dispatch($deposit->id)
                ->delay(now()->addSeconds(30))
                ->onQueue((string) config('custody.queues.verification', 'verification'));
            return;
        }

        $verified = $verifier->verify($deposit->fresh(), $this->observedContract);
        if ($verified->status === Deposit::STATUS_VERIFIED) {
            CreditVerifiedDeposit::dispatch($verified->id)
                ->onQueue((string) config('custody.queues.crediting', 'crediting'));
        }
    }
}

