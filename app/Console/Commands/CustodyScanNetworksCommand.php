<?php

namespace App\Console\Commands;

use App\Jobs\Wallet\ScanBlockchain;
use App\Models\Wallet\BlockchainNetwork;
use Illuminate\Console\Command;

class CustodyScanNetworksCommand extends Command
{
    protected $signature = 'custody:scan-networks';

    protected $description = 'Dispatch blockchain deposit scan jobs for all enabled custody networks';

    public function handle(): int
    {
        $testnetOnly = (bool) config('custody.testnet_only', true);
        $networks = BlockchainNetwork::where('is_enabled', true)->get();

        $dispatched = 0;
        foreach ($networks as $network) {
            if ($testnetOnly && !$network->is_testnet) {
                continue;
            }
            ScanBlockchain::dispatch($network->id)
                ->onQueue((string) config('custody.queues.monitoring', 'monitoring'));
            $dispatched++;
        }

        $this->info("Dispatched ScanBlockchain for {$dispatched} enabled network(s).");

        return self::SUCCESS;
    }
}
