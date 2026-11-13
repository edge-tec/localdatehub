<?php

namespace App\Console\Commands;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\GasReserve;
use App\Models\Wallet\RecoveryQueueItem;
use App\Services\Blockchain\BlockchainMonitor;
use App\Services\Security\SigningGateway;
use App\Services\Support\BaseUnitMath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CustodyHealthCheckCommand extends Command
{
    protected $signature = 'custody:health-check';

    protected $description = 'Monitor signer availability, RPC health, gas reserves, and recovery backlog';

    public function handle(SigningGateway $signingGateway, BlockchainMonitor $monitor): int
    {
        $issues = [];

        $signerHealth = $signingGateway->provider()->healthCheck();
        if (!($signerHealth['ok'] ?? false)) {
            $issues[] = 'Signer unavailable: ' . ($signerHealth['message'] ?? 'unknown');
        }

        foreach (BlockchainNetwork::where('is_enabled', true)->get() as $network) {
            try {
                $height = $monitor->adapterFor($network)->getLatestBlockHeight($network);
                if ($height <= 0) {
                    $issues[] = "RPC returned invalid block height for [{$network->network_code}]";
                }
            } catch (\Throwable $e) {
                $issues[] = "RPC unreachable for [{$network->network_code}]: {$e->getMessage()}";
            }
        }

        foreach (GasReserve::with('network')->get() as $reserve) {
            if (BaseUnitMath::comp((string) $reserve->available_balance_base_units, (string) $reserve->minimum_threshold_base_units) < 0) {
                $issues[] = "Low gas reserve on [{$reserve->network?->network_code}]";
            }
        }

        $recoveryCount = RecoveryQueueItem::whereIn('status', ['PENDING', 'ESCALATED'])->count();
        if ($recoveryCount > 0) {
            $issues[] = "{$recoveryCount} item(s) waiting in recovery queue";
        }

        if (!empty($issues)) {
            Log::warning('Custody operational health alert', ['issues' => $issues]);
            foreach ($issues as $issue) {
                $this->warn($issue);
            }

            return self::FAILURE;
        }

        $this->info('Custody operational health OK.');

        return self::SUCCESS;
    }
}
