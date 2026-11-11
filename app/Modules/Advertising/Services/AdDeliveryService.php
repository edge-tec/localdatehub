<?php

namespace App\Modules\Advertising\Services;

use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Models\AdClick;
use App\Modules\Advertising\Models\AdCreative;
use App\Modules\Advertising\Models\AdDailyStatistic;
use App\Modules\Advertising\Models\AdImpression;
use App\Modules\Advertising\Models\AdPlacementConfig;
use App\Modules\Advertising\Models\AdTargetingRule;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AdDeliveryService
{
    protected const CACHE_VERSION_KEY = 'ads:delivery:cache_version';

    /** @var array<int, bool> */
    protected array $requestSeenCounted = [];

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

        $context = $this->resolveDeliveryContext($context);
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
                // If already counted during THIS HTTP request, allow other slots on the same page to render cleanly
                if ($seen >= $campaign->frequency_cap_per_session && !$this->wasCountedInCurrentRequest($campaign->id)) {
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
        $isBot = $request && $this->trackingService->isBotRequest($request);
        $payloads = $selected->map(function (AdCampaign $campaign) use ($slot, $device, $context, $request, $recordImpressions, $isBot) {
            $creative = $campaign->creatives->where('is_active', true)->first();
            $payload = $this->formatCreativePayload($campaign, $creative, $slot, $device);

            if ($recordImpressions && $request && !$isBot) {
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
        if ($neededAds <= 0 && $items->isNotEmpty() && $maxAds > 0) {
            // Ensure sparse listing feeds (fewer items than insert_every_n) still deliver 1 eligible ad
            $neededAds = 1;
        }
        if ($neededAds <= 0) {
            return $items->map(fn ($listing) => ['type' => 'listing', 'listing' => $listing])->values();
        }

        $context = $this->resolveDeliveryContext($context);
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

        // If listing count was smaller than insert_every_n, append 1 fetched ad at the end of the feed
        if ($items->count() < $everyN && $adIndex === 0 && $adsPool->isNotEmpty()) {
            $output->push(['type' => 'ad', 'ad' => $adsPool->get(0)]);
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
     * Enrich caller-supplied ad context with safe request geo/device signals when omitted.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function resolveDeliveryContext(array $context = []): array
    {
        $req = app()->bound('request') ? request() : null;
        if (!$req) {
            return $context;
        }

        if (!array_key_exists('state', $context) || $context['state'] === null || $context['state'] === '') {
            $headerState = $req->header('CF-Region-Code')
                ?: $req->header('X-State-Code')
                ?: $req->header('X-Region-Code');
            $headerCountry = strtoupper((string) ($req->header('CF-IPCountry') ?: $req->header('X-Country-Code') ?: ''));
            if ($headerState && strlen(trim((string) $headerState)) === 2 && ($headerCountry === '' || $headerCountry === 'US')) {
                $context['state'] = strtoupper(trim((string) $headerState));
            }
        }

        if ((!array_key_exists('country', $context) || $context['country'] === null || $context['country'] === '') && $req->query('country')) {
            $qCountry = strtoupper(trim((string) $req->query('country')));
            if (strlen($qCountry) === 2) {
                $context['country'] = $qCountry;
            }
        }

        return $context;
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
        $ctxState = strtoupper(trim((string) ($context['state'] ?? '')));
        $ctxCityId = isset($context['city_id']) ? trim((string) $context['city_id']) : '';
        $ctxZip = trim((string) ($context['zip_code'] ?? ''));
        $ctxCatId = isset($context['category_id']) ? trim((string) $context['category_id']) : '';
        $ctxParentCatId = isset($context['parent_category_id']) ? trim((string) $context['parent_category_id']) : '';
        $ctxDevice = strtolower($device);
        $strictTargeting = !empty($context['strict_targeting']);

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
        $optionalUnscopedTypes = [
            AdTargetingRule::RULE_STATE,
            AdTargetingRule::RULE_CITY,
            AdTargetingRule::RULE_ZIP_CODE,
            AdTargetingRule::RULE_CATEGORY,
            AdTargetingRule::RULE_SUBCATEGORY,
        ];

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
            if ($actual === []) {
                // Global/unfiltered pages (Homepage, unfiltered Search, Footer, or State/Category pages lacking the other filter)
                // should not suppress active campaigns simply because the route lacks a dimension filter.
                if (!$strictTargeting && in_array($type, $optionalUnscopedTypes, true)) {
                    continue;
                }

                return false;
            }

            if (array_intersect($allowed, $actual) === []) {
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
        $desktopUrl = AdCreative::normalizeAssetUrl($creative?->desktop_banner_url);
        $mobileUrl = AdCreative::normalizeAssetUrl($creative?->mobile_banner_url);
        $thumbnailUrl = AdCreative::normalizeAssetUrl($creative?->thumbnail_url);
        $imageUrl = $creative?->resolvedImageUrl($device);
        $destination = $creative?->resolvedTargetUrl() ?: $campaign->destination_url;

        return [
            'is_legacy' => false,
            'campaign_id' => $campaign->id,
            'campaign_uuid' => $campaign->uuid,
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
            'desktop_banner_url' => $desktopUrl,
            'mobile_banner_url' => $mobileUrl,
            'thumbnail_url' => $thumbnailUrl,
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

    protected function wasCountedInCurrentRequest(int $campaignId): bool
    {
        if (app()->bound('request')) {
            $map = (array) request()->attributes->get('_ads_seen_counted', []);

            return isset($map[$campaignId]);
        }

        return isset($this->requestSeenCounted[$campaignId]);
    }

    protected function markSessionSeen(int $campaignId): void
    {
        if (!app()->bound('session') || $this->wasCountedInCurrentRequest($campaignId)) {
            return;
        }

        if (app()->bound('request')) {
            $map = (array) request()->attributes->get('_ads_seen_counted', []);
            $map[$campaignId] = true;
            request()->attributes->set('_ads_seen_counted', $map);
        } else {
            $this->requestSeenCounted[$campaignId] = true;
        }

        $counts = (array) session()->get('ads_seen_counts', []);
        $counts[$campaignId] = ((int) ($counts[$campaignId] ?? 0)) + 1;
        session()->put('ads_seen_counts', $counts);
    }

    /**
     * Evaluate full real-time ad delivery health for a campaign across all eligibility, creative, placement, targeting & tracking gates.
     *
     * @param  array<string, mixed>  $sampleContext
     * @return array<string, mixed>
     */
    public function diagnoseCampaign(AdCampaign $campaign, array $sampleContext = []): array
    {
        $campaign->loadMissing(['creatives', 'placements', 'targetingRules']);

        $now = now();
        $blockingReasons = [];
        $warnings = [];

        // 1. Lifecycle Status
        $statusOk = in_array($campaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_SCHEDULED], true);
        if (!$statusOk) {
            $blockingReasons[] = "Campaign status is '" . str_replace('_', ' ', $campaign->status) . "' (must be active to serve).";
        } elseif ($campaign->status === AdCampaign::STATUS_SCHEDULED && (!$campaign->starts_at || $campaign->starts_at->lte($now))) {
            $warnings[] = 'Status is still marked scheduled even though its start time has arrived.';
        }

        // 2. Schedule Dates
        $startsOk = !$campaign->starts_at || $campaign->starts_at->lte($now);
        $endsOk = !$campaign->ends_at || $campaign->ends_at->gt($now);
        $scheduleOk = $startsOk && $endsOk;

        if (!$startsOk) {
            $blockingReasons[] = 'Start schedule (' . $campaign->starts_at->format('M j, Y H:i') . ' ' . config('app.timezone', 'UTC') . ') is in the future (server now: ' . $now->format('M j, Y H:i') . ' ' . config('app.timezone', 'UTC') . ').';
        }
        if (!$endsOk) {
            $blockingReasons[] = 'End schedule (' . $campaign->ends_at->format('M j, Y H:i') . ' ' . config('app.timezone', 'UTC') . ') has already passed.';
        }

        // 3. Caps & Budget
        $todayStats = $this->getTodayStatsMap([$campaign->id]);
        $today = $todayStats[$campaign->id] ?? ['impressions' => 0, 'clicks' => 0, 'spend_cents' => 0];
        $dailyCapOk = $this->passesDailyCaps($campaign, $today);

        $totalBudgetOk = $campaign->total_budget_cents === null || $campaign->spent_cents < $campaign->total_budget_cents;
        $totalImprOk = $campaign->max_impressions === null || $campaign->total_impressions < $campaign->max_impressions;
        $totalClickOk = $campaign->max_clicks === null || $campaign->total_clicks < $campaign->max_clicks;
        $capsOk = $dailyCapOk && $totalBudgetOk && $totalImprOk && $totalClickOk;

        if (!$totalBudgetOk) {
            $blockingReasons[] = 'Total campaign budget has been exhausted.';
        }
        if (!$totalImprOk) {
            $blockingReasons[] = 'Maximum lifetime impression limit reached.';
        }
        if (!$totalClickOk) {
            $blockingReasons[] = 'Maximum lifetime click limit reached.';
        }
        if (!$dailyCapOk) {
            $blockingReasons[] = 'Daily budget, impression, or click cap reached for today.';
        }

        // 4. Creative & Asset Verification
        $activeCreative = $campaign->creatives->where('is_active', true)->first();
        $hasActiveCreative = $activeCreative !== null;
        $desktopUrl = AdCreative::normalizeAssetUrl($activeCreative?->desktop_banner_url);
        $mobileUrl = AdCreative::normalizeAssetUrl($activeCreative?->mobile_banner_url);
        $thumbUrl = AdCreative::normalizeAssetUrl($activeCreative?->thumbnail_url);
        $destUrl = $activeCreative?->resolvedTargetUrl() ?: $campaign->destination_url;
        $desktopFileOk = $this->assetExists($desktopUrl);
        $mobileFileOk = $this->assetExists($mobileUrl);
        $hasSnippet = !empty($activeCreative?->html_snippet);
        $hasVisualOrCopy = !empty($desktopUrl) || !empty($mobileUrl) || !empty($thumbUrl) || $hasSnippet || !empty($activeCreative?->title);

        if (!$hasActiveCreative) {
            $blockingReasons[] = 'No active creative found for this campaign.';
        } elseif (!$hasVisualOrCopy) {
            $blockingReasons[] = 'Creative has no banner image, HTML snippet, or title configured.';
        }
        if ($desktopUrl && !$desktopFileOk) {
            $warnings[] = "Desktop banner file missing on public storage disk ({$desktopUrl}).";
        }
        if ($mobileUrl && !$mobileFileOk) {
            $warnings[] = "Mobile banner file missing on public storage disk ({$mobileUrl}).";
        }
        if ($campaign->campaign_type === AdCampaign::TYPE_BANNER && !$desktopUrl && !$mobileUrl && !$hasSnippet) {
            $warnings[] = 'Banner campaign has no desktop or mobile banner image uploaded yet (rendering text fallback).';
        }
        $destOk = AdUrlService::isSafeDestinationUrl($destUrl);
        if (!$destOk) {
            $warnings[] = 'Destination URL is missing or invalid (clicks will fall back to homepage).';
        }

        // 5. Placement Slots & API Delivery Check
        $activePlacements = $campaign->placements->where('is_active', true)->values();
        if ($activePlacements->isEmpty()) {
            $blockingReasons[] = 'No active placement slots assigned to this campaign.';
        }

        $placementDiagnostics = [];
        $anySlotEnabled = false;
        $anyApiDelivered = false;
        foreach ($campaign->placements as $pl) {
            $slot = (string) $pl->placement_slot;
            $cfg = $this->getPlacementConfig($slot);
            $slotEnabled = (bool) $cfg->is_enabled && (bool) $pl->is_active;
            if ($slotEnabled) {
                $anySlotEnabled = true;
            }
            if (!$cfg->is_enabled) {
                $warnings[] = 'Placement slot [' . (AdCampaign::SLOT_CATALOG[$slot]['label'] ?? $slot) . '] is currently disabled in Slot Architecture.';
            }

            $testDevice = $slot === AdCampaign::SLOT_MOBILE_FEED ? 'mobile' : ($slot === AdCampaign::SLOT_DESKTOP_FEED ? 'desktop' : ($sampleContext['device'] ?? 'desktop'));
            $served = $slotEnabled ? $this->getAdsForSlot($slot, $sampleContext + ['device' => $testDevice], 10, false) : collect();
            $servedSelf = $served->contains(fn (array $item) => (int) ($item['campaign_id'] ?? 0) === (int) $campaign->id);
            if ($servedSelf) {
                $anyApiDelivered = true;
            }

            $placementDiagnostics[] = [
                'slot' => $slot,
                'label' => AdCampaign::SLOT_CATALOG[$slot]['label'] ?? ucwords(str_replace('_', ' ', $slot)),
                'is_active' => (bool) $pl->is_active,
                'slot_enabled' => (bool) $cfg->is_enabled,
                'insert_every_n' => (int) $cfg->insert_every_n,
                'max_ads_per_page' => (int) $cfg->max_ads_per_page,
                'rotation_mode' => (string) $cfg->rotation_mode,
                'api_delivered' => $servedSelf,
                'api_url' => route('ads.api.serve', ['placement' => $slot]),
            ];
        }

        if ($activePlacements->isNotEmpty() && !$anySlotEnabled) {
            $blockingReasons[] = 'All assigned placement slots are disabled in Slot Architecture.';
        }

        // 6. Targeting Summary
        $rules = $campaign->targetingRules;
        $geoRules = $rules->whereIn('rule_type', [
            AdTargetingRule::RULE_COUNTRY,
            AdTargetingRule::RULE_STATE,
            AdTargetingRule::RULE_CITY,
            AdTargetingRule::RULE_ZIP_CODE,
        ]);
        $deviceRules = $rules->where('rule_type', AdTargetingRule::RULE_DEVICE);
        $categoryRules = $rules->whereIn('rule_type', [AdTargetingRule::RULE_CATEGORY, AdTargetingRule::RULE_SUBCATEGORY]);

        $geoSummary = $geoRules->isEmpty()
            ? 'Global / All Locations'
            : $geoRules->groupBy('rule_type')->map(fn ($g, $t) => strtoupper($t) . ': ' . $g->take(4)->pluck('rule_value')->implode(', ') . ($g->count() > 4 ? ' +' . ($g->count() - 4) : ''))->implode(' · ');

        $deviceSummary = $deviceRules->isEmpty()
            ? 'All Devices (Desktop, Mobile, Tablet)'
            : $deviceRules->pluck('rule_value')->map(fn ($v) => ucfirst((string) $v))->implode(', ');

        $categorySummary = $categoryRules->isEmpty()
            ? 'All Categories'
            : $categoryRules->count() . ' Category Rule(s)';

        $sampleDevice = strtolower((string) ($sampleContext['device'] ?? ($deviceRules->first()?->rule_value ?: 'desktop')));
        $targetingMatchesSample = $this->matchesTargetingRules($campaign, $sampleContext, $sampleDevice);
        if (!$targetingMatchesSample) {
            $blockingReasons[] = 'Current targeting rules do not match the default visitor context.';
        }

        // 7. Recent Telemetry
        $lastImpression = Schema::hasTable('ad_impressions')
            ? AdImpression::where('campaign_id', $campaign->id)->latest('created_at')->first()
            : null;
        $lastClick = Schema::hasTable('ad_clicks')
            ? AdClick::where('campaign_id', $campaign->id)->where('is_suspicious', false)->latest('created_at')->first()
            : null;

        $isEligible = $statusOk && $scheduleOk && $capsOk && $hasActiveCreative && $anySlotEnabled && $targetingMatchesSample && $blockingReasons === [];

        $deliveryStatus = match (true) {
            $isEligible && $anyApiDelivered => 'LIVE DELIVERING',
            !$statusOk && $campaign->status === AdCampaign::STATUS_PAUSED => 'PAUSED',
            !$statusOk && $campaign->status === AdCampaign::STATUS_DRAFT => 'DRAFT',
            !$endsOk || $campaign->status === AdCampaign::STATUS_EXPIRED => 'EXPIRED',
            !$startsOk || $campaign->status === AdCampaign::STATUS_SCHEDULED => 'SCHEDULED',
            !$capsOk => 'CAPPED',
            default => 'BLOCKED',
        };

        $primarySlot = $activePlacements->first()?->placement_slot ?? AdCampaign::SLOT_BETWEEN_LISTINGS;

        return [
            'campaign_id' => $campaign->id,
            'campaign_uuid' => $campaign->uuid,
            'campaign_name' => $campaign->name,
            'status' => $campaign->status,
            'eligible' => $isEligible,
            'eligibility_label' => $isEligible ? 'ELIGIBLE' : 'NOT ELIGIBLE',
            'delivery_status' => $deliveryStatus,
            'blocking_reasons' => $blockingReasons,
            'warnings' => $warnings,
            'schedule' => [
                'starts_at' => $campaign->starts_at?->toIso8601String(),
                'starts_at_human' => $campaign->starts_at ? $campaign->starts_at->format('M j, Y H:i T') : 'Immediate',
                'ends_at' => $campaign->ends_at?->toIso8601String(),
                'ends_at_human' => $campaign->ends_at ? $campaign->ends_at->format('M j, Y H:i T') : 'Ongoing',
                'server_now_human' => $now->format('M j, Y H:i T'),
                'starts_ok' => $startsOk,
                'ends_ok' => $endsOk,
                'schedule_ok' => $scheduleOk,
            ],
            'placements' => $placementDiagnostics,
            'creative' => [
                'has_active_creative' => $hasActiveCreative,
                'format' => $activeCreative?->format ?? $campaign->campaign_type,
                'desktop_banner_url' => $desktopUrl,
                'desktop_file_ok' => $desktopFileOk,
                'mobile_banner_url' => $mobileUrl,
                'mobile_file_ok' => $mobileFileOk,
                'thumbnail_url' => $thumbUrl,
                'destination_url' => $destUrl,
                'destination_ok' => $destOk,
                'has_html_snippet' => $hasSnippet,
            ],
            'api_delivery' => [
                'overall_ok' => $isEligible && $anyApiDelivered,
                'api_endpoint' => route('ads.api.serve', ['placement' => $primarySlot]),
                'primary_slot' => $primarySlot,
            ],
            'targeting' => [
                'geo_summary' => $geoSummary,
                'geo_ok' => $targetingMatchesSample,
                'device_summary' => $deviceSummary,
                'category_summary' => $categorySummary,
                'rules_count' => $rules->count(),
            ],
            'caps' => [
                'frequency_cap_per_session' => $campaign->frequency_cap_per_session,
                'daily_impression_cap' => $campaign->daily_impression_cap,
                'daily_click_cap' => $campaign->daily_click_cap,
                'today_impressions' => $today['impressions'],
                'today_clicks' => $today['clicks'],
                'total_impressions' => (int) $campaign->total_impressions,
                'max_impressions' => $campaign->max_impressions,
                'total_clicks' => (int) $campaign->total_clicks,
                'max_clicks' => $campaign->max_clicks,
                'spent_usd' => round(((int) $campaign->spent_cents) / 100, 2),
                'total_budget_usd' => $campaign->total_budget_cents !== null ? round($campaign->total_budget_cents / 100, 2) : null,
                'caps_ok' => $capsOk,
            ],
            'telemetry' => [
                'last_impression_at' => $lastImpression?->created_at?->toIso8601String(),
                'last_impression_human' => $lastImpression?->created_at ? $lastImpression->created_at->diffForHumans() . ' (' . $lastImpression->created_at->format('M j, H:i T') . ')' : 'Never',
                'last_impression_slot' => $lastImpression?->placement_slot,
                'last_click_at' => $lastClick?->created_at?->toIso8601String(),
                'last_click_human' => $lastClick?->created_at ? $lastClick->created_at->diffForHumans() . ' (' . $lastClick->created_at->format('M j, H:i T') . ')' : 'Never',
                'last_click_slot' => $lastClick?->placement_slot,
            ],
        ];
    }

    protected function assetExists(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        if (str_starts_with($url, '/storage/')) {
            $rel = ltrim(substr($url, strlen('/storage/')), '/');

            return $rel !== '' && Storage::disk('public')->exists($rel);
        }

        return AdUrlService::isSafeImageUrl($url);
    }
}
