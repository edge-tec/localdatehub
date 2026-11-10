<?php

namespace App\Console\Commands;

use App\Modules\SEO\Services\GoogleSearchConsoleService;
use Illuminate\Console\Command;

class SeoGscSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'seo:gsc-sync';

    /**
     * The console command description.
     */
    protected $description = 'Sync search performance analytics from Google Search Console (GSC) API';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSearchConsoleService $gscService): int
    {
        $this->info('Starting Google Search Console analytics synchronization...');
        $result = $gscService->syncAnalytics();

        if ($result['success']) {
            $this->info($result['message'] . ' Synced at: ' . ($result['synced_at'] ?? now()->toDateTimeString()));
            return self::SUCCESS;
        }

        $this->warn('GSC Sync notice: ' . ($result['message'] ?? 'Could not complete sync.'));
        return self::SUCCESS;
    }
}

