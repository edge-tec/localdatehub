<?php

namespace App\Console\Commands;

use App\Modules\Billing\Services\PromotionService;
use Illuminate\Console\Command;

class ExpirePromotionsCommand extends Command
{
    protected $signature = 'monetization:expire-promotions';
    protected $description = 'Expire outdated listing promotions and reset featured statuses';

    public function handle(PromotionService $promotionService): int
    {
        $this->info('Scanning and expiring outdated listing promotions...');
        $count = $promotionService->expireOutdatedPromotions();

        $this->info("Expired {$count} outdated promotions.");
        return self::SUCCESS;
    }
}
