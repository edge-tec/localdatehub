<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\BlockchainTransaction;
use App\Services\Blockchain\BlockchainMonitor;
use App\Services\Deposit\DepositReorganizationHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectBlockchainReorganization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(public int $networkId)
    {
        $this->onQueue((string) config('custody.queues.monitoring', 'monitoring'));
    }

    public function handle(BlockchainMonitor $monitor, DepositReorganizationHandler $reorgHandler): void
    {
        $network = BlockchainNetwork::findOrFail($this->networkId);
        $adapter = $monitor->adapterFor($network);

        BlockchainTransaction::where('network_id', $network->id)
            ->whereNotNull('block_height')
            ->whereNotNull('block_hash')
            ->whereIn('transaction_status', ['DETECTED', 'CONFIRMING', 'VERIFIED'])
            ->chunkById(50, function ($txs) use ($adapter, $network, $reorgHandler) {
                foreach ($txs as $tx) {
                    $chainHash = $adapter->getBlockHash($network, (int) $tx->block_height);
                    if ($chainHash !== null && strtolower($chainHash) !== strtolower((string) $tx->block_hash)) {
                        $reorgHandler->handleReorg($tx, 'BLOCK_HASH_MISMATCH');
                    }
                }
            });
    }
}

