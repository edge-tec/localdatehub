<?php

namespace App\Console\Commands;

use App\Services\CampaignAutomationService;
use Illuminate\Console\Command;

class PublishFolderCampaignCommand extends Command
{
    protected $signature = 'app:publish-folder-campaign
        {path : Path to a profile image folder or parent folder containing profile subfolders}
        {--email=manmarzi001@gmail.com : Authorized contact email for the advertiser profile}
        {--category= : Category slug or ID (defaults to women-seeking-men)}
        {--age=25 : Advertiser age (minimum 18)}
        {--gender=female : Poster gender}
        {--intent=dating : Listing intent}
        {--state= : Optional state code, name, or slug filter}
        {--city= : Optional city name or slug filter}
        {--limit=0 : Maximum number of eligible cities to process (0 = all)}
        {--dry-run : Validate locations, SEO copy, and image selection without writing to the database}';

    protected $description = 'Automate folder-based dating classifieds publishing across verified states and cities with random 1-3 images and unique SEO content.';

    public function handle(CampaignAutomationService $automation): int
    {
        $path = (string) $this->argument('path');
        $email = (string) $this->option('email');

        $this->info(($this->option('dry-run') ? '[DRY RUN] ' : '') . "Starting folder campaign for: {$path}");

        $report = $automation->runCampaign($path, $email, [
            'category' => $this->option('category'),
            'age' => (int) $this->option('age'),
            'gender' => (string) $this->option('gender'),
            'intent' => (string) $this->option('intent'),
            'state' => $this->option('state'),
            'city' => $this->option('city'),
            'limit' => (int) $this->option('limit'),
            'dry_run' => (bool) $this->option('dry-run'),
        ]);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Folders discovered', $report['total_folders_discovered']],
                ['Authorized profiles created', $report['total_profiles_created']],
                ['Existing profiles reused', $report['total_profiles_reused']],
                ['States processed', $report['total_states_processed']],
                ['Eligible cities', $report['total_cities_eligible']],
                ['Cities processed', $report['total_cities_processed']],
                ['Ads published (active)', $report['total_ads_published']],
                ['Ads pending review', $report['total_ads_pending_review']],
                ['Total images uploaded', $report['total_images_uploaded']],
                ['1-image / 2-image / 3-image ads', implode(' / ', [
                    $report['image_count_breakdown'][1] ?? 0,
                    $report['image_count_breakdown'][2] ?? 0,
                    $report['image_count_breakdown'][3] ?? 0,
                ])],
                ['Duplicate content detected', $report['duplicate_content_detected']],
                ['Failed / quota-blocked ads', count($report['failed_advertisements'])],
                ['Unavailable cities', count($report['unavailable_cities'])],
            ]
        );

        $this->info('Markdown report: ' . ($report['report_files']['markdown'] ?? 'N/A'));
        $this->info('JSON report: ' . ($report['report_files']['json'] ?? 'N/A'));

        return self::SUCCESS;
    }
}

