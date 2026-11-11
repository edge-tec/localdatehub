<?php

namespace App\Console\Commands;

use App\Modules\Advertising\Services\AdDeliveryService;
use Illuminate\Console\Command;

class SyncAdCampaignsCommand extends Command
{
    protected $signature = 'ads:sync-lifecycle';

    protected $description = 'Activate ready scheduled ad campaigns and mark ended campaigns as expired';

    public function handle(AdDeliveryService $deliveryService): int
    {
        $updated = $deliveryService->syncLifecycleStatuses();
        $this->info("Synchronized {$updated} advertising campaign lifecycle statuses.");

        return self::SUCCESS;
    }
}

