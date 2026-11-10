<?php

namespace App\Modules\SEO\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\Models\CmsPage;
use App\Modules\SEO\Models\SeoIssue;
use App\Modules\SEO\Models\SeoUrlRecord;
use Illuminate\Support\Facades\DB;

class TechnicalSeoAuditService
{
    /**
     * Run a comprehensive technical SEO audit across all indexed content.
     */
    public function runFullAudit(): array
    {
        // Clear previous unresolved automatic issues to refresh with live checks
        SeoIssue::where('is_resolved', false)->delete();

        $issuesFound = 0;

        // 1. Audit active listings
        $listings = Listing::where('status', Listing::STATUS_ACTIVE)
            ->with(['photos', 'category', 'state', 'city'])
            ->get();

        $titlesSeen = [];
        $descriptionsSeen = [];

        foreach ($listings as $listing) {
            $url = route('listings.show', $listing->slug);
            $urlRecord = SeoUrlRecord::firstOrCreate(
                ['url' => $url],
                [
                    'url_type' => 'listing',
                    'model_type' => get_class($listing),
                    'model_id' => $listing->id,
                    'canonical_url' => $url,
                    'http_status' => 200,
                    'meta_title' => $listing->title,
                    'meta_description' => \Illuminate\Support\Str::limit(strip_tags($listing->description), 155),
                    'h1' => $listing->title,
                ]
            );

            $title = trim($listing->title);
            $desc = trim(strip_tags($listing->description));

            // Check Title
            if (empty($title)) {
                $this->createIssue($urlRecord, $url, 'missing_title', 'critical', 'Missing meta title tag.', 'Add a descriptive 50-60 character title.');
                $issuesFound++;
            } elseif (isset($titlesSeen[$title])) {
                $this->createIssue($urlRecord, $url, 'duplicate_title', 'warning', "Duplicate title detected with listing ID #{$titlesSeen[$title]}.", 'Ensure every listing title has distinctive localized keywords.');
                $issuesFound++;
            } else {
                $titlesSeen[$title] = $listing->id;
            }

            // Check Description
            if (empty($desc)) {
                $this->createIssue($urlRecord, $url, 'missing_description', 'critical', 'Missing meta description.', 'Add a compelling 140-160 character description.');
                $issuesFound++;
            } elseif (strlen($desc) < 50) {
                $this->createIssue($urlRecord, $url, 'thin_content', 'warning', "Thin content detected (" . strlen($desc) . " chars).", 'Expand listing details to provide high-quality context.');
                $issuesFound++;
            } elseif (isset($descriptionsSeen[$desc])) {
                $this->createIssue($urlRecord, $url, 'duplicate_description', 'notice', 'Duplicate meta description found.', 'Customize listing details to be unique.');
                $issuesFound++;
            } else {
                $descriptionsSeen[$desc] = $listing->id;
            }

            // Check Photo Alt text
            foreach ($listing->photos as $photo) {
                if (empty(trim($photo->original_filename ?? ''))) {
                    $this->createIssue($urlRecord, $url, 'missing_alt', 'notice', "Listing media #{$photo->id} is missing alt text.", 'Provide descriptive keyword alt text for image accessibility and Image SEO.');
                    $issuesFound++;
                    break;
                }
            }
        }

        // 2. Audit empty locations (Local SEO check)
        $emptyCities = City::where('is_active', true)
            ->whereDoesntHave('listings', function ($q) {
                $q->where('status', Listing::STATUS_ACTIVE);
            })
            ->with('state')
            ->limit(50)
            ->get();

        foreach ($emptyCities as $city) {
            if ($city->state) {
                $url = route('locations.city', ['stateCode' => strtolower($city->state->code), 'citySlug' => $city->slug]);
                $urlRecord = SeoUrlRecord::where('url', $url)->first();
                if ($urlRecord) {
                    $this->createIssue(
                        $urlRecord,
                        $url,
                        'thin_content',
                        'notice',
                        "Location page for {$city->name}, {$city->state->code} has zero active listings.",
                        'Apply noindex or curate featured announcements until active local listings exist.'
                    );
                    $issuesFound++;
                }
            }
        }

        // 3. Recalculate Health Scores
        $totalUrls = SeoUrlRecord::count();
        $urlsWithIssues = SeoIssue::where('is_resolved', false)->distinct('url')->count('url');

        // Update each url record has_issues flag
        DB::table('seo_url_records')->update(['has_issues' => false, 'seo_health_score' => 100]);

        $affectedRecordIds = SeoIssue::where('is_resolved', false)
            ->whereNotNull('url_record_id')
            ->pluck('url_record_id')
            ->unique();

        if ($affectedRecordIds->isNotEmpty()) {
            DB::table('seo_url_records')
                ->whereIn('id', $affectedRecordIds)
                ->update(['has_issues' => true, 'seo_health_score' => 75]);
        }

        $overallHealth = $totalUrls > 0 ? max(10, (int) round((($totalUrls - $urlsWithIssues) / $totalUrls) * 100)) : 100;

        return [
            'total_urls' => $totalUrls,
            'issues_found' => $issuesFound,
            'urls_with_issues' => $urlsWithIssues,
            'overall_health_score' => $overallHealth,
        ];
    }

    protected function createIssue(SeoUrlRecord $record, string $url, string $type, string $severity, string $description, string $recommendation): SeoIssue
    {
        return SeoIssue::create([
            'url_record_id' => $record->id,
            'url' => $url,
            'issue_type' => $type,
            'severity' => $severity,
            'description' => $description,
            'recommendation' => $recommendation,
            'is_resolved' => false,
        ]);
    }
}

