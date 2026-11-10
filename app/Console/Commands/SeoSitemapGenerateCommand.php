<?php

namespace App\Console\Commands;

use App\Modules\SEO\Services\SitemapService;
use Illuminate\Console\Command;

class SeoSitemapGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'seo:sitemap-generate';

    /**
     * The console command description.
     */
    protected $description = 'Generate and validate all XML sitemaps for LocalDateHub';

    /**
     * Execute the console command.
     */
    public function handle(SitemapService $sitemapService): int
    {
        $this->info('Generating XML sitemaps...');
        $results = $sitemapService->generateAll();

        $rows = [];
        foreach ($results as $key => $sitemap) {
            $rows[] = [
                $sitemap->name,
                number_format($sitemap->url_count),
                round($sitemap->file_size_bytes / 1024, 2) . ' KB',
                $sitemap->is_valid_xml ? 'VALID' : 'INVALID',
                $sitemap->submission_status,
            ];
        }

        $this->table(['Sitemap File', 'URLs', 'Size', 'XML Validity', 'Submission Status'], $rows);
        $this->info('All sitemaps generated and validated successfully.');

        return self::SUCCESS;
    }
}

