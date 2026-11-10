<?php

namespace App\Modules\SEO\Observers;

use App\Modules\Listings\Models\Listing;
use App\Modules\SEO\Models\SeoRedirect;
use App\Modules\SEO\Models\SeoUrlRecord;
use App\Modules\SEO\Services\GoogleIndexingService;
use Illuminate\Support\Str;

class ListingSeoObserver
{
    /**
     * Handle listing saved/created/updated/renewed.
     */
    public function saved(Listing $listing): void
    {
        if (empty($listing->slug)) {
            return;
        }

        $url = route('listings.show', $listing->slug);

        if ($listing->status === Listing::STATUS_ACTIVE && $listing->published_at && (! $listing->expires_at || $listing->expires_at->isFuture())) {
            // Eligible for public SEO & Sitemaps
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

            // Clean up any stale 410 redirect if it was previously marked gone
            $path = '/listings/' . $listing->slug;
            SeoRedirect::where('source_path', $path)->delete();

            // Enqueue for Google Search Console / Sitemaps indexing pipeline
            app(GoogleIndexingService::class)->enqueue($url, 'URL_UPDATED', 'sitemap_sync', 5, $listing);
        } else {
            // Ineligible (expired, paused, rejected, under review)
            // Note: Do NOT mark 410 Gone since listing can be renewed or unpaused!
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

    /**
     * Handle listing deletion.
     * Only apply 410 Gone if permanently deleted (forceDeleted).
     */
    public function deleted(Listing $listing): void
    {
        if (empty($listing->slug)) {
            return;
        }

        $url = route('listings.show', $listing->slug);
        $path = '/listings/' . $listing->slug;

        // If permanently deleted, assign 410 Gone so search engines immediately drop it
        if ($listing->isForceDeleting()) {
            SeoRedirect::updateOrCreate(
                ['source_path' => $path],
                [
                    'status_code' => 410,
                    'is_active' => true,
                    'notes' => "Listing #{$listing->id} permanently deleted from database",
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
        } else {
            // Soft-deleted: Exclude from sitemaps and indexing, but DO NOT apply 410 Gone
            // because soft-deleted listings can be restored by the user or moderator.
            $record = SeoUrlRecord::where('url', $url)->first();
            if ($record) {
                $record->update([
                    'indexing_status' => 'excluded',
                    'indexing_eligibility' => 'ineligible',
                    'sitemap_status' => 'excluded',
                ]);
            }
        }

        // Notify indexing pipeline
        app(GoogleIndexingService::class)->enqueue($url, 'URL_DELETED', 'sitemap_sync', 8, null);
    }

    /**
     * Handle listing restored from trash.
     */
    public function restored(Listing $listing): void
    {
        $this->saved($listing);
    }
}
