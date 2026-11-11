<?php

namespace App\Modules\Advertising\Services;

use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Models\AdCreative;
use App\Modules\Advertising\Models\AdDailyStatistic;
use App\Modules\Advertising\Models\AdPlacementConfig;
use App\Modules\Advertising\Models\AdTargetingRule;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AdDeliveryService
{
    protected const CACHE_VERSION_KEY = 'ads:delivery:cache_version';

    public function __construct(
        protected AdTrackingService $trackingService
    ) {}

    public function clearCache(): void
    {
        try {
            if (Cache::has(self::CACHE_VERSION_KEY)) {
                Cache::increment(self::CACHE_VERSION_KEY);
            } else {
                Cache::put(self::CACHE_VERSION_KEY, 2, now()->addDays(30));
            }
        } catch (\Throwable) {
            // Safe fallback on cache store failure
        }
    }

    public function detectDeviceType(?Request $request = null): string
    {
        $req = $request ?? (app()->bound('request') ? request() : null);
        if (!$req) {
            return 'desktop';
        }

        $ua = strtolower((string) $req->userAgent());
        if ($ua === '') {
            return 'desktop';
        }

        if (preg_match('/(ipad|tablet|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/(mobile|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop|android.*mobile)/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public function getPlacementConfig(string $slot): AdPlacementConfig
    {
        $version = (int) Cache::get(self::CACHE_VERSION_KEY, 1);
        $cacheKey = "ads:slot_config_data:v{$version}:{$slot}";

        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && isset($cached['slot'])) {
                $model = new AdPlacementConfig();
                $model->forceFill($cached);
                $model->exists = isset($cached['id']);

                return $model;
            }
        } catch (\Throwable) {
            Cache::forget($cacheKey);
        }

        $config = AdPlacementConfig::forSlot($slot);

        try {
            Cache::put($cacheKey, $config->attributesToArray(), 300);
        } catch (\Throwable) {
            // Safe fallback if cache store is unavailable
        }

        return $config;
    }

    /**
     * Transition expired and ready-scheduled campaigns cleanly.
     */
    public function syncLifecycleStatuses(): int
    {
        if (!Schema::hasTable('ad_campaigns')) {
            return 0;
        }

        $now = now();
        $expired = AdCampaign::whereIn('status', [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_SCHEDULED])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->update(['status' => AdCampaign::STATUS_EXPIRED, 'updated_at' => $now]);

        $activated = AdCampaign::where('status', AdCampaign::STATUS_SCHEDULED)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->update(['status' => AdCampaign::STATUS_ACTIVE, 'updated_at' => $now]);

        if ($expired + $activated > 0) {
            $this->clearCache();
        }

        return $expired + $activated;
    }

    /**
     * Return eligible rendered creative payloads for a placement slot and context.
     *
     * @param  array<string, mixed>  $context
     * @return Collection<int, array<string, mixed>>
     */
    public function getAdsForSlot(string $slot, array $context = [], ?int $limit = null, bool $recordImpressions = true): Collection
    {
        if (!Schema::hasTable('ad_campaigns')) {
            return collect();
        }

        $config = $this->getPlacementConfig($slot);
        if (!$config->is_enabled) {
            return collect();
        }

        $effectiveLimit = $limit ?? max(1, (int) $config->max_ads_per_page);
        $device = strtolower((string) ($context['device'] ?? $this->detectDeviceType()));
        $excludeIds = collect($context['exclude_campaign_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();

        // Enforce device-specific slot guards
        if ($slot === AdCampaign::SLOT_MOBILE_FEED && $device !== 'mobile') {
            return collect();
        }
        if ($slot === AdCampaign::SLOT_DESKTOP_FEED && $device === 'mobile') {
            return collect();
        }

        $candidates = $this->getCachedSlotCandidates($slot);

        // Daily budget & daily cap usage map
        $todayStats = $this->getTodayStatsMap($candidates->pluck('id')->all());

        // Session frequency & deduplication
        $sessionSeen = $this->getSessionSeenCounts();

        $eligible = $candidates->filter(function (AdCampaign $campaign) use ($context, $device, $excludeIds, $todayStats, $sessionSeen) {
            if (in_array((int) $campaign->id, $excludeIds, true)) {
                return false;
            }
            if (!$campaign->isCurrentlyEligible()) {
                return false;
            }
            if ($campaign->creatives->where('is_active', true)->isEmpty()) {
                return false;
            }
            if (!$this->passesDailyCaps($campaign, $todayStats[$campaign->id] ?? null)) {
                return false;
            }
            if ($campaign->frequency_cap_per_session !== null && $campaign->frequency_cap_per_session > 0) {
                $seen = (int) ($sessionSeen[$campaign->id] ?? 0);
                if ($seen >= $campaign->frequency_cap_per_session) {
                    return false;
                }
            }

            return $this->matchesTargetingRules($campaign, $context, $device);
        })->values();

        // Session deduplication: prefer unseen campaigns first, fall back to seen only if needed
        if ($config->dedupe_session && $eligible->count() > $effectiveLimit) {
            $unseen = $eligible->filter(fn (AdCampaign $c) => (int) ($sessionSeen[$c->id] ?? 0) === 0)->values();
            if ($unseen->isNotEmpty()) {
                $seenFallback = $eligible->filter(fn (AdCampaign $c) => (int) ($sessionSeen[$c->id] ?? 0) > 0)->values();
                $eligible = $unseen->concat($seenFallback);
            }
        }

        $selected = $this->rotateCandidates($eligible, $config->rotation_mode, $slot)
            ->take($effectiveLimit)
            ->values();

        $request = app()->bound('request') ? request() : null;
        $payloads = $selected->map(function (AdCampaign $campaign) use ($slot, $device, $context, $request, $recordImpressions) {
            $creative = $campaign->creatives->where('is_active', true)->first();
            $payload = $this->formatCreativePayload($campaign, $creative, $slot, $device);

            if ($recordImpressions && $request) {
                $this->trackingService->recordImpression($campaign, $creative, $slot, $request, $context + ['device' => $device]);
                $this->markSessionSeen($campaign->id);
            }

            return $payload;
        });

        // Backward-compatible fallback to legacy AdPlacement records ONLY when no enterprise campaigns exist for this slot
        if ($candidates->isEmpty() && $payloads->isEmpty() && Schema::hasTable('ad_placements')) {
            $payloads = $this->getLegacyFallbackAds($slot, $effectiveLimit);
        }

        return $payloads;
    }

    /**
     * Merge regular classified listings with dynamically injected advertisements.
     *
     * @param  iterable<int, mixed>  $listings
     * @param  array<string, mixed>  $context
     * @return Collection<int, array<string, mixed>>
     */
    public function injectIntoListings(iterable $listings, string $slot = AdCampaign::SLOT_BETWEEN_LISTINGS, array $context = [], bool $recordImpressions = true): Collection
    {
        $items = collect($listings);
        if ($items->isEmpty()) {
            return collect();
        }

        $config = $this->getPlacementConfig($slot);
        $everyN = max(1, (int) ($context['insert_every_n'] ?? $config->insert_every_n));
        $maxAds = max(0, (int) ($context['max_ads'] ?? $config->max_ads_per_page));

        if (!$config->is_enabled || $maxAds === 0) {
            return $items->map(fn ($listing) => ['type' => 'listing', 'listing' => $listing])->values();
        }

        $neededAds = min($maxAds, (int) floor($items->count() / $everyN));
        if ($neededAds <= 0) {
            return $items->map(fn ($listing) => ['type' => 'listing', 'listing' => $listing])->values();
        }

        $device = strtolower((string) ($context['device'] ?? $this->detectDeviceType()));

        // Combine slot-specific campaigns + 'between_listing_cards' + device feed if applicable
        $adsPool = $this->getAdsForSlot($slot, $context, $neededAds, $recordImpressions);

        if ($adsPool->count() < $neededAds && $slot !== AdCampaign::SLOT_BETWEEN_LISTINGS) {
            $usedIds = $adsPool->pluck('campaign_id')->filter()->all();
            $extra = $this->getAdsForSlot(
                AdCampaign::SLOT_BETWEEN_LISTINGS,
                array_merge($context, ['exclude_campaign_ids' => array_merge($context['exclude_campaign_ids'] ?? [], $usedIds)]),
                $neededAds - $adsPool->count(),
                $recordImpressions
            );
            $adsPool = $adsPool->concat($extra);
        }

        if ($adsPool->count() < $neededAds) {
            $deviceSlot = $device === 'mobile' ? AdCampaign::SLOT_MOBILE_FEED : AdCampaign::SLOT_DESKTOP_FEED;
            $usedIds = $adsPool->pluck('campaign_id')->filter()->all();
            $extraDevice = $this->getAdsForSlot(
                $deviceSlot,
                array_merge($context, ['exclude_campaign_ids' => array_merge($context['exclude_campaign_ids'] ?? [], $usedIds)]),
                $neededAds - $adsPool->count(),
                $recordImpressions
            );
            $adsPool = $adsPool->concat($extraDevice);
        }

        $output = collect();
        $adIndex = 0;

        foreach ($items->values() as $idx => $listing) {
            $output->push(['type' => 'listing', 'listing' => $listing]);

            $position = $idx + 1;
            if ($position % $everyN === 0 && $adIndex < $adsPool->count()) {
                $output->push(['type' => 'ad', 'ad' => $adsPool->get($adIndex)]);
                $adIndex++;
            }
        }

        return $output;
    }

    /**
     * @return Collection<int, AdCampaign>
     */
    protected function getCachedSlotCandidates(string $slot): Collection
    {
        $version = (int) Cache::get(self::CACHE_VERSION_KEY, 1);
        $cacheKey = "ads:slot_candidate_ids:v{$version}:{$slot}";

        $ids = null;
        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $ids = array_values(array_filter(array_map('intval', $cached)));
            }
        } catch (\Throwable) {
            Cache::forget($cacheKey);
        }

        if ($ids === null) {
            $ids = AdCampaign::query()
                ->eligibleForDelivery()
                ->whereHas('placements', fn ($q) => $q->where('placement_slot', $slot)->where('is_active', true))
                ->orderByDesc('priority')
                ->orderByDesc('weight')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            try {
                Cache::put($cacheKey, $ids, 120);
            } catch (\Throwable) {
                // Safe fallback if cache store is unavailable
            }
        }

        if ($ids === []) {
            return collect();
        }

        return AdCampaign::query()
            ->with(['creatives' => fn ($q) => $q->where('is_active', true), 'placements', 'targetingRules'])
            ->whereIn('id', $ids)
            ->eligibleForDelivery()
            ->orderByDesc('priority')
            ->orderByDesc('weight')
            ->get();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array<string, int>>
     */
    protected function getTodayStatsMap(array $campaignIds): array
    {
        if ($campaignIds === []) {
            return [];
        }

        return AdDailyStatistic::query()
            ->whereDate('date', now()->toDateString())
            ->whereIn('campaign_id', $campaignIds)
            ->selectRaw('campaign_id, SUM(impressions) as impr, SUM(clicks) as clk, SUM(spend_cents) as spnd')
            ->groupBy('campaign_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->campaign_id => [
                    'impressions' => (int) $row->impr,
                    'clicks' => (int) $row->clk,
                    'spend_cents' => (int) $row->spnd,
                ],
            ])
            ->all();
    }

    /**
     * @param  array<string, int>|null  $today
     */
    protected function passesDailyCaps(AdCampaign $campaign, ?array $today): bool
    {
        if (!$today) {
            return true;
        }
        if ($campaign->daily_budget_cents !== null && $today['spend_cents'] >= $campaign->daily_budget_cents) {
            return false;
        }
        if ($campaign->daily_impression_cap !== null && $today['impressions'] >= $campaign->daily_impression_cap) {
            return false;
        }
        if ($campaign->daily_click_cap !== null && $today['clicks'] >= $campaign->daily_click_cap) {
            return false;
        }

        return true;
    }

    /**
     * Evaluate Country, State, City, ZIP, Category, Subcategory, and Device rules.
     *
     * @param  array<string, mixed>  $context
     */
    public function matchesTargetingRules(AdCampaign $campaign, array $context, string $device): bool
    {
        $rules = $campaign->targetingRules;
        if ($rules->isEmpty()) {
            return true;
        }

        $ctxCountry = strtoupper((string) ($context['country'] ?? 'US'));
        $ctxState = strtoupper((string) ($context['state'] ?? ''));
        $ctxCityId = isset($context['city_id']) ? (string) $context['city_id'] : '';
        $ctxZip = trim((string) ($context['zip_code'] ?? ''));
        $ctxCatId = isset($context['category_id']) ? (string) $context['category_id'] : '';
        $ctxParentCatId = isset($context['parent_category_id']) ? (string) $context['parent_category_id'] : '';
        $ctxDevice = strtolower($device);

        $contextValues = [
            AdTargetingRule::RULE_COUNTRY => array_filter([$ctxCountry]),
            AdTargetingRule::RULE_STATE => array_filter([$ctxState]),
            AdTargetingRule::RULE_CITY => array_filter([$ctxCityId]),
            AdTargetingRule::RULE_ZIP_CODE => array_filter([$ctxZip]),
            AdTargetingRule::RULE_CATEGORY => array_filter([$ctxCatId, $ctxParentCatId]),
            AdTargetingRule::RULE_SUBCATEGORY => array_filter([$ctxCatId]),
            AdTargetingRule::RULE_DEVICE => array_filter([$ctxDevice]),
        ];

        // 1. Check exclusions first
        foreach ($rules->where('match_mode', AdTargetingRule::MODE_EXCLUDE) as $rule) {
            $ruleVal = strtoupper(trim($rule->rule_value));
            $candidates = array_map('strtoupper', $contextValues[$rule->rule_type] ?? []);
            if (in_array($ruleVal, $candidates, true)) {
                return false;
            }
        }

        // 2. Check inclusion rules grouped by type (AND across types, OR within a type)
        $includesByType = $rules->where('match_mode', AdTargetingRule::MODE_INCLUDE)->groupBy('rule_type');

        foreach ($includesByType as $type => $typeRules) {
            $allowed = $typeRules->pluck('rule_value')->map(fn ($v) => strtoupper(trim((string) $v)))->all();
            if (in_array('ALL', $allowed, true)) {
                continue;
            }

            // Combine category + subcategory context when category rules exist
            if ($type === AdTargetingRule::RULE_CATEGORY && isset($includesByType[AdTargetingRule::RULE_SUBCATEGORY])) {
                $allowed = array_merge($allowed, $includesByType[AdTargetingRule::RULE_SUBCATEGORY]->pluck('rule_value')->map(fn ($v) => strtoupper(trim((string) $v)))->all());
            }

            $actual = array_map('strtoupper', $contextValues[$type] ?? []);
            if ($actual === [] || array_intersect($allowed, $actual) === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, AdCampaign>  $candidates
     * @return Collection<int, AdCampaign>
     */
    protected function rotateCandidates(Collection $candidates, string $mode, string $slot): Collection
    {
        if ($candidates->count() <= 1) {
            return $candidates;
        }

        if ($mode === AdPlacementConfig::ROTATION_RANDOM) {
            return $candidates->shuffle()->values();
        }

        if ($mode === AdPlacementConfig::ROTATION_ROUND_ROBIN) {
            $offsetKey = "ads:rr_offset:{$slot}";
            $offset = (int) Cache::get($offsetKey, 0);
            Cache::put($offsetKey, ($offset + 1) % $candidates->count(), now()->addHours(1));
            $arr = $candidates->values()->all();
            $rotated = array_merge(array_slice($arr, $offset % count($arr)), array_slice($arr, 0, $offset % count($arr)));

            return collect($rotated);
        }

        // priority_weighted: keep priority tiers descending, weighted-shuffle within each priority tier
        return $candidates
            ->groupBy('priority')
            ->sortKeysDesc()
            ->flatMap(function (Collection $tier) {
                $pool = $tier->values()->all();
                $ordered = [];
                while (count($pool) > 0) {
                    $totalWeight = array_sum(array_map(fn ($c) => max(1, (int) $c->weight), $pool));
                    $rand = mt_rand(1, max(1, $totalWeight));
                    $running = 0;
                    foreach ($pool as $idx => $item) {
                        $running += max(1, (int) $item->weight);
                        if ($rand <= $running) {
                            $ordered[] = $item;
                            array_splice($pool, $idx, 1);
                            break;
                        }
                    }
                }

                return $ordered;
            })
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatCreativePayload(AdCampaign $campaign, ?AdCreative $creative, string $slot, string $device = 'desktop'): array
    {
        $format = $creative?->format ?? ($campaign->campaign_type === AdCampaign::TYPE_AFFILIATE ? AdCreative::FORMAT_AFFILIATE : AdCreative::FORMAT_BANNER);
        $imageUrl = $creative?->resolvedImageUrl($device);
        $destination = $creative?->resolvedTargetUrl() ?: $campaign->destination_url;

        return [
            'is_legacy' => false,
            'campaign_id' => $campaign->id,
            'creative_id' => $creative?->id,
            'slot' => $slot,
            'format' => $format,
            'campaign_name' => $campaign->name,
            'title' => $creative?->title ?: $campaign->name,
            'description' => $creative?->short_description ?: $campaign->description,
            'advertiser_name' => $creative?->affiliate_brand_name ?: ($campaign->advertiser_name ?: 'Sponsored Partner'),
            'cta_text' => $creative?->cta_text ?: ($format === AdCreative::FORMAT_AFFILIATE ? 'Claim Offer' : 'Learn More'),
            'disclosure_label' => $creative?->disclosure_label ?: ($format === AdCreative::FORMAT_AFFILIATE ? 'Affiliate Offer' : 'Sponsored'),
            'offer_badge' => $creative?->offer_badge,
            'offer_expires_at' => $creative?->offer_expires_at,
            'image_url' => $imageUrl,
            'desktop_banner_url' => $creative?->desktop_banner_url,
            'mobile_banner_url' => $creative?->mobile_banner_url,
            'thumbnail_url' => $creative?->thumbnail_url,
            'alt_text' => $creative?->alt_text ?: ($creative?->title ?: $campaign->name),
            'html_snippet' => AdUrlService::sanitizeHtmlSnippet($creative?->html_snippet),
            'target_url' => $destination,
            'click_url' => route('ads.click', [
                'campaign' => $campaign->uuid,
                'slot' => $slot,
                'creative' => $creative?->id,
            ]),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function getLegacyFallbackAds(string $slot, int $limit): Collection
    {
        return AdPlacement::live()
            ->where('slot', $slot)
            ->orderByDesc('updated_at')
            ->take($limit)
            ->get()
            ->map(fn (AdPlacement $ad) => [
                'is_legacy' => true,
                'legacy_id' => $ad->id,
                'campaign_id' => null,
                'creative_id' => null,
                'slot' => $slot,
                'format' => 'banner',
                'campaign_name' => $ad->name,
                'title' => $ad->name,
                'description' => null,
                'advertiser_name' => $ad->advertiser_name ?: 'Sponsored Partner',
                'cta_text' => 'Visit Site',
                'disclosure_label' => 'Advertisement',
                'offer_badge' => null,
                'offer_expires_at' => null,
                'image_url' => $ad->image_url,
                'desktop_banner_url' => $ad->image_url,
                'mobile_banner_url' => $ad->image_url,
                'thumbnail_url' => $ad->image_url,
                'alt_text' => $ad->name,
                'html_snippet' => AdUrlService::sanitizeHtmlSnippet($ad->html_snippet),
                'target_url' => $ad->target_url,
                'click_url' => route('ads.legacy.click', ['placement' => $ad->id, 'slot' => $slot]),
            ]);
    }

    /**
     * @return array<int, int>
     */
    protected function getSessionSeenCounts(): array
    {
        if (!app()->bound('session')) {
            return [];
        }

        return (array) session()->get('ads_seen_counts', []);
    }

    protected function markSessionSeen(int $campaignId): void
    {
        if (!app()->bound('session')) {
            return;
        }

        $counts = (array) session()->get('ads_seen_counts', []);
        $counts[$campaignId] = ((int) ($counts[$campaignId] ?? 0)) + 1;
        session()->put('ads_seen_counts', $counts);
    }
}
