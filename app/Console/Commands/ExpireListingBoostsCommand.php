<?php

namespace App\Console\Commands;

use App\Modules\Payments\Crypto\Services\BoostExpirationService;
use Illuminate\Console\Command;

class ExpireListingBoostsCommand extends Command
{
    protected $signature = 'boosts:expire';
    protected $description = 'Automatically scan and expire post boosts that have reached their expiration date';

    public function handle(BoostExpirationService $service): int
    {
        $this->info('Scanning for expired listing boosts...');
        $count = $service->expireOutdatedBoosts();
        $this->info("Successfully expired {$count} listing boost(s) and restored normal ranking.");

        return Command::SUCCESS;
    }
}

