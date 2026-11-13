<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\WalletAddress;
use App\Services\Deposit\DepositDetector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIncomingTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 60;
    public array $backoff = [10, 30, 60, 120];

    /**
     * @param array<string, mixed> $txData
     */
    public function __construct(
        public int $walletAddressId,
        public array $txData
    ) {
        $this->onQueue((string) config('custody.queues.verification', 'verification'));
    }

    public function handle(DepositDetector $detector): void
    {
        $address = WalletAddress::findOrFail($this->walletAddressId);
        $deposit = $detector->recordObservedDeposit($address, $this->txData);

        VerifyBlockchainTransaction::dispatch($deposit->id, $this->txData['contract_address'] ?? null)
            ->onQueue((string) config('custody.queues.verification', 'verification'));
    }
}

