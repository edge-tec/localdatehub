<?php

namespace App\Modules\Listings\Services;

use App\Modules\Listings\Models\AnalyticsEvent;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingAnalytics;
use App\Modules\Listings\Models\SearchTrend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsTrackingService
{
    /**
     * Compute a privacy-safe one-way salted hash of visitor characteristics.
     * No raw IP addresses, precise coordinates, or persistent PII are stored.
     */
    public function computeVisitorHash(Request $request): string
    {
        $ip = $request->ip() ?: '127.0.0.1';
        $ua = $request->userAgent() ?: 'unknown';
        $salt = config('app.key', 'LocalAdZoneSaltKey2026');

        return hash('sha256', $ip . '|' . $ua . '|' . $salt);
    }

    /**
     * Record a privacy-conscious listing view and update daily aggregated metrics.
     * Prevents reload inflation, bot views, and self-views by the author/admin.
     */
    public function recordListingView(Listing $listing, Request $request): void
    {
        $ua = (string) ($request->userAgent() ?: '');
        if (preg_match('/(bot|crawl|spider|slurp|facebookexternalhit|bingbot|googlebot|headless|lighthouse)/i', $ua)) {
            return;
        }

        // Do not count self-views when the listing author or an admin reloads the listing
        $viewer = $request->user();
        if ($viewer && ((int) $viewer->id === (int) $listing->user_id || $viewer->is_admin || in_array($viewer->role, ['super_admin', 'admin', 'moderator'], true))) {
            return;
        }

        $hash = $this->computeVisitorHash($request);
        $today = now()->toDateString();

        // Check if this visitor reloaded the same listing within the last 30 minutes
        $reloadedWithinCooldown = AnalyticsEvent::where('listing_id', $listing->id)
            ->where('event_type', 'listing_view')
            ->where('visitor_hash', $hash)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($reloadedWithinCooldown) {
            return;
        }

        // 1. Check if this visitor already viewed today (unique visitor check)
        $alreadyViewedToday = AnalyticsEvent::where('listing_id', $listing->id)
            ->where('event_type', 'listing_view')
            ->where('visitor_hash', $hash)
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        // 2. Insert event
        AnalyticsEvent::create([
            'event_type' => 'listing_view',
            'listing_id' => $listing->id,
            'category_id' => $listing->category_id,
            'state_id' => $listing->state_id,
            'city_id' => $listing->city_id,
            'visitor_hash' => $hash,
            'metadata' => [
                'is_featured' => (bool) $listing->is_featured,
            ],
            'created_at' => now(),
        ]);

        // 3. Increment listing view_count only once per unique visitor per day (or outside cooldown)
        if (! $alreadyViewedToday) {
            $listing->increment('view_count');
        }

        // 4. Update daily aggregated ListingAnalytics record (tracks both Gross views and Unique visitors)
        $analytics = ListingAnalytics::where('listing_id', $listing->id)
            ->whereDate('metric_date', $today)
            ->first();

        if (! $analytics) {
            $analytics = ListingAnalytics::create([
                'listing_id' => $listing->id,
                'metric_date' => $today,
                'views' => 0,
                'unique_visitors' => 0,
                'contact_clicks' => 0,
                'favorite_adds' => 0,
            ]);
        }

        $analytics->increment('views');
        if (! $alreadyViewedToday) {
            $analytics->increment('unique_visitors');
        }
    }

    /**
     * Record interaction conversion (contact click or favorite toggle).
     */
    public function recordInteraction(Listing $listing, string $type, Request $request): void
    {
        $hash = $this->computeVisitorHash($request);
        $today = now()->toDateString();

        AnalyticsEvent::create([
            'event_type' => $type,
            'listing_id' => $listing->id,
            'category_id' => $listing->category_id,
            'state_id' => $listing->state_id,
            'city_id' => $listing->city_id,
            'visitor_hash' => $hash,
            'created_at' => now(),
        ]);

        $analytics = ListingAnalytics::where('listing_id', $listing->id)
            ->whereDate('metric_date', $today)
            ->first();

        if (! $analytics) {
            $analytics = ListingAnalytics::create([
                'listing_id' => $listing->id,
                'metric_date' => $today,
                'views' => 0,
                'unique_visitors' => 0,
                'contact_clicks' => 0,
                'favorite_adds' => 0,
            ]);
        }

        if ($type === 'contact_click') {
            $analytics->increment('contact_clicks');
            $listing->increment('reply_count');
        } elseif ($type === 'favorite_add') {
            $analytics->increment('favorite_adds');
            $listing->increment('favorite_count');
        }
    }

    /**
     * Record search query trend.
     */
    public function recordSearchQuery(
        string $query,
        int $resultsCount,
        ?int $categoryId = null,
        ?int $stateId = null,
        ?int $cityId = null
    ): void {
        $cleaned = trim(strtolower(substr($query, 0, 150)));
        if (empty($cleaned)) {
            return;
        }

        $trend = SearchTrend::firstOrCreate(
            [
                'query' => $cleaned,
                'category_id' => $categoryId,
                'state_id' => $stateId,
                'city_id' => $cityId,
            ],
            [
                'results_count' => $resultsCount,
                'hits' => 0,
                'last_searched_at' => now(),
            ]
        );

        $trend->results_count = $resultsCount;
        $trend->last_searched_at = now();
        $trend->increment('hits');
    }

    /**
     * Get aggregate performance metrics for analytics dashboards.
     */
    public function getPerformanceSummary(int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'total_views' => (int) ListingAnalytics::where('metric_date', '>=', $since)->sum('views'),
            'total_unique' => (int) ListingAnalytics::where('metric_date', '>=', $since)->sum('unique_visitors'),
            'total_contacts' => (int) ListingAnalytics::where('metric_date', '>=', $since)->sum('contact_clicks'),
            'total_favorites' => (int) ListingAnalytics::where('metric_date', '>=', $since)->sum('favorite_adds'),
            'top_searches' => SearchTrend::orderByDesc('hits')->limit(10)->get(['query', 'hits', 'results_count']),
            'zero_result_searches' => SearchTrend::where('results_count', 0)->orderByDesc('hits')->limit(10)->get(['query', 'hits']),
        ];
    }
}