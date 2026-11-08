<?php

namespace App\Console\Commands;

use App\Modules\Billing\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessRenewalsCommand extends Command
{
    protected $signature = 'monetization:process-renewals';
    protected $description = 'Process due membership and subscription renewals';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $this->info('Processing due subscription renewals...');
        $result = $subscriptionService->processRenewals();

        $this->info("Renewals processed: {$result['processed']}, failed: {$result['failed']}");
        return self::SUCCESS;
    }
}
