<?php

namespace App\Modules\SEO\Observers;

use App\Modules\Listings\Models\Listing;
use App\Modules\SEO\Models\SeoRedirect;
use App\Modules\SEO\Models\SeoUrlRecord;
use App\Modules\SEO\Services\GoogleIndexingService;
use Illuminate\Support\Str;

class ListingSeoObserver
{
    public function saved(Listing $listing): void
    {
        if (empty($listing->slug)) {
            return;
        }

        $url = route('listings.show', $listing->slug);

        if ($listing->status === Listing::STATUS_ACTIVE && $listing->published_at && (! $listing->expires_at || $listing->expires_at->isFuture())) {
            // Eligible for public SEO & Indexing
            $title = $listing->title;
            $desc = Str::limit(strip_tags($listing->description), 155);

            $record = SeoUrlRecord::updateOrCreate(
                ['url' => $url],
                [
                    'url_type' => 'listing',
                    'model_type' => get_class($listing),
                    'model_id' => $listing->id,
                    'canonical_url' => $url,
                    'indexing_status' => 'queued',
                    'indexing_eligibility' => 'eligible',
                    'sitemap_status' => 'included',
                    'http_status' => 200,
                    'meta_title' => $title,
                    'meta_description' => $desc,
                    'h1' => $title,
                ]
            );

            // Enqueue for Google discovery
            app(GoogleIndexingService::class)->enqueue($url, 'URL_UPDATED', 'sitemap_ping', 5, $listing);
        } else {
            // Ineligible (expired, paused, rejected)
            $record = SeoUrlRecord::where('url', $url)->first();
            if ($record) {
                $record->update([
                    'indexing_status' => 'excluded',
                    'indexing_eligibility' => 'ineligible',
                    'sitemap_status' => 'excluded',
                ]);
            }
        }
    }

    public function deleted(Listing $listing): void
    {
        if (empty($listing->slug)) {
            return;
        }

        $url = route('listings.show', $listing->slug);
        $path = '/listings/' . $listing->slug;

        // Register 410 Gone for search engines
        SeoRedirect::updateOrCreate(
            ['source_path' => $path],
            [
                'status_code' => 410,
                'is_active' => true,
                'notes' => "Listing #{$listing->id} permanently deleted",
            ]
        );

        $record = SeoUrlRecord::where('url', $url)->first();
        if ($record) {
            $record->update([
                'indexing_status' => 'excluded',
                'indexing_eligibility' => 'deleted',
                'sitemap_status' => 'excluded',
                'http_status' => 410,
            ]);
        }

        // Enqueue deletion notification
        app(GoogleIndexingService::class)->enqueue($url, 'URL_DELETED', 'sitemap_ping', 8, null);
    }
}

