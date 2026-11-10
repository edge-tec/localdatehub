<?php

namespace App\Console\Commands;

use App\Modules\ContentGeneration\Services\DatingListingGeneratorService;
use App\Modules\SEO\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateDatingListingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dating:generate-listings 
                            {--per-city=20 : Number of listings per city}
                            {--state= : Optional state code (e.g. TX, CA, FL)}
                            {--max= : Maximum total listings to create}
                            {--regenerate-sitemaps : Automatically regenerate XML sitemaps after generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate humanized, SEO-friendly dating listings using uploaded images with encrypted contact details';

    /**
     * Execute the console command.
     */
    public function handle(
        DatingListingGeneratorService $generator,
        SitemapService $sitemapService
    ): int {
        $perCity = (int) $this->option('per-city') ?: 20;
        $state = $this->option('state');
        $max = $this->option('max') ? (int) $this->option('max') : null;

        $this->info("===================================================================");
        $this->info(" Starting USA Dating Content Generation & Publishing System");
        $this->info(" Target: {$perCity} listings/city" . ($state ? " | State: {$state}" : " | All States"));
        $this->info(" Contact Encryption: carriethengd@gmail.com (AES-256)");
        $this->info(" Zero Duplicate Image Policy: Enforced");
        $this->info("===================================================================");

        $result = $generator->generate([
            'per_city' => $perCity,
            'state_code' => $state,
            'max_listings' => $max,
        ]);

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Listings Created', $result['created']],
                ['Cities Covered', $result['cities_covered']],
                ['Unique Images Deployed', $result['images_used']],
                ['Contact Security', 'carriethengd@gmail.com (Encrypted in DB)'],
                ['Policy Status', $result['paused_reason'] ?: 'Completed Successfully'],
            ]
        );

        if (!empty($result['listings'])) {
            $this->newLine();
            $this->info("Sample Generated Listings (First 5):");
            $sample = array_slice($result['listings'], 0, 5);
            $this->table(['ID', 'Title', 'City', 'State', 'Image File'], array_map(function ($l) {
                return [$l['id'], $l['title'], $l['city'], $l['state'], $l['image']];
            }, $sample));
        }

        if ($result['paused_reason']) {
            $this->warn("Note: " . $result['paused_reason']);
        }

        // Regenerate XML Sitemaps if requested or by default
        if ($this->option('regenerate-sitemaps') || $result['created'] > 0) {
            $this->info(">>> Regenerating XML sitemaps to include new published listings...");
            try {
                $sitemaps = $sitemapService->generateAll();
                $listingsCount = $sitemaps['listings']->url_count ?? 0;
                $this->info(">>> Sitemaps regenerated successfully ({$listingsCount} active listings indexed).");
            } catch (\Throwable $e) {
                $this->warn(">>> Sitemap regeneration warning: " . $e->getMessage());
            }
        }

        $this->info("Done! Content generation & publishing process complete.");
        return Command::SUCCESS;
    }
}
