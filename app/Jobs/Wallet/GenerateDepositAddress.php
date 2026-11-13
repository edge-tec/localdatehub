<?php

namespace App\Jobs\Wallet;

use App\Models\User;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Services\Wallet\HDWalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDepositAddress implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [5, 15, 30];

    public function __construct(
        public int $userId,
        public int $networkId,
        public int $assetId
    ) {
        $this->onQueue((string) config('custody.queues.monitoring', 'monitoring'));
    }

    public function uniqueId(): string
    {
        return "gen-addr-{$this->userId}-{$this->networkId}-{$this->assetId}";
    }

    public function handle(HDWalletService $hdWallet): void
    {
        $user = User::findOrFail($this->userId);
        $network = BlockchainNetwork::findOrFail($this->networkId);
        $asset = CryptoAsset::findOrFail($this->assetId);

        $hdWallet->getOrAssignDepositAddress($user, $network, $asset);
    }
}

