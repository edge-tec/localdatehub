<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\WalletAddress;
use App\Services\Blockchain\BlockchainMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanBlockchain implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [10, 30, 60];

    public function __construct(public int $networkId)
    {
        $this->onQueue((string) config('custody.queues.monitoring', 'monitoring'));
    }

    public function uniqueId(): string
    {
        return "scan-net-{$this->networkId}";
    }

    public function handle(BlockchainMonitor $monitor): void
    {
        $network = BlockchainNetwork::where('id', $this->networkId)->where('is_enabled', true)->first();
        if (!$network) {
            return;
        }

        $adapter = $monitor->adapterFor($network);
        $latest = $adapter->getLatestBlockHeight($network);
        $fromHeight = (int) ($network->checkpoint?->last_processed_height ?? 0);

        WalletAddress::where('network_id', $network->id)
            ->where('status', 'ACTIVE')
            ->with('asset')
            ->chunkById(50, function ($addresses) use ($adapter, $network, $fromHeight) {
                foreach ($addresses as $addr) {
                    if (!$addr->asset) {
                        continue;
                    }
                    $txs = $adapter->fetchIncomingTransactions($network, $addr->asset, $addr->address, $fromHeight);
                    foreach ($txs as $txData) {
                        ProcessIncomingTransaction::dispatch($addr->id, $txData)
                            ->onQueue((string) config('custody.queues.verification', 'verification'));
                    }
                }
            });

        if ($latest > 0) {
            $monitor->updateCheckpoint($network, $latest, $adapter->getBlockHash($network, $latest));
        }
    }
}

