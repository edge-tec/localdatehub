<?php

namespace App\Console\Commands;

use App\Modules\SEO\Services\GoogleSearchConsoleService;
use Illuminate\Console\Command;

class SeoGoogleInspectCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'seo:google-inspect {url : The absolute public URL to inspect}';

    /**
     * The console command description.
     */
    protected $description = 'Inspect URL indexing and canonical status using Google Search Console URL Inspection API (2,000 queries/day quota protected)';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSearchConsoleService $gscService): int
    {
        $url = (string) $this->argument('url');
        $this->info("Requesting Google URL Inspection for: {$url}");

        $result = $gscService->inspectUrl($url);

        if (! empty($result['quota_exceeded'])) {
            $this->error('Google URL Inspection daily quota reached (2,000 queries/day limit).');
            return self::FAILURE;
        }

        if (! empty($result['success'])) {
            $this->table(['Metric', 'Google Response'], [
                ['URL', $url],
                ['Verdict', $result['verdict'] ?? 'UNKNOWN'],
                ['Coverage State', $result['coverage'] ?? 'N/A'],
                ['Inspection Status', 'Completed'],
            ]);
            return self::SUCCESS;
        }

        $this->warn('Notice: ' . ($result['message'] ?? 'Inspection failed. Verify GSC service account credentials.'));
        return self::SUCCESS;
    }
}

