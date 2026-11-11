<?php

namespace App\Modules\Advertising\Models;

use App\Models\User;
use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Advertising\Services\AdDeliveryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdCampaign extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_SCHEDULED,
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_EXPIRED,
        self::STATUS_REJECTED,
        self::STATUS_ARCHIVED,
    ];

    public const TYPE_BANNER = 'banner';
    public const TYPE_AFFILIATE = 'affiliate';
    public const TYPE_HYBRID = 'hybrid';

    public const TYPES = [
        self::TYPE_BANNER,
        self::TYPE_AFFILIATE,
        self::TYPE_HYBRID,
    ];

    public const SLOT_HOMEPAGE_FEATURED = 'homepage_featured';
    public const SLOT_HOMEPAGE_FEED = 'homepage_feed';
    public const SLOT_BETWEEN_LISTINGS = 'between_listing_cards';
    public const SLOT_CATEGORY_PAGE = 'category_page';
    public const SLOT_SEARCH_RESULTS = 'search_results';
    public const SLOT_LOCATION_PAGE = 'location_page';
    public const SLOT_SIDEBAR = 'sidebar';
    public const SLOT_LISTING_DETAIL = 'listing_detail';
    public const SLOT_RELATED_LISTINGS = 'related_listings';
    public const SLOT_MOBILE_FEED = 'mobile_feed';
    public const SLOT_DESKTOP_FEED = 'desktop_feed';
    public const SLOT_HOMEPAGE_TOP = 'homepage_top';
    public const SLOT_FOOTER = 'footer';

    public const SLOTS = [
        self::SLOT_HOMEPAGE_FEATURED,
        self::SLOT_HOMEPAGE_FEED,
        self::SLOT_BETWEEN_LISTINGS,
        self::SLOT_CATEGORY_PAGE,
        self::SLOT_SEARCH_RESULTS,
        self::SLOT_LOCATION_PAGE,
        self::SLOT_SIDEBAR,
        self::SLOT_LISTING_DETAIL,
        self::SLOT_RELATED_LISTINGS,
        self::SLOT_MOBILE_FEED,
        self::SLOT_DESKTOP_FEED,
        self::SLOT_HOMEPAGE_TOP,
        self::SLOT_FOOTER,
    ];

    public const SLOT_CATALOG = [
        self::SLOT_HOMEPAGE_FEATURED => [
            'label' => 'Homepage Featured Advertisement',
            'dimensions' => 'Featured Card / 300x400',
            'device' => 'All Devices',
            'description' => 'Featured sponsor spotlight integrated directly into Homepage Featured Listings.',
            'default_every_n' => 4,
            'default_max_per_page' => 2,
        ],
        self::SLOT_HOMEPAGE_FEED => [
            'label' => 'Homepage Listing Feed',
            'dimensions' => 'Native Card / 728x90',
            'device' => 'All Devices',
            'description' => 'Promotional cards blended inside the Homepage Latest Local Listings feed.',
            'default_every_n' => 4,
            'default_max_per_page' => 2,
        ],
        self::SLOT_BETWEEN_LISTINGS => [
            'label' => 'Between Listing Cards',
            'dimensions' => 'Native Card / Leaderboard',
            'device' => 'All Devices',
            'description' => 'Dynamic in-feed sponsored units injected after every N classified listings.',
            'default_every_n' => 6,
            'default_max_per_page' => 3,
        ],
        self::SLOT_CATEGORY_PAGE => [
            'label' => 'Category Listing Pages',
            'dimensions' => '728x90 & Native Card',
            'device' => 'All Devices',
            'description' => 'Targeted category header banners and in-feed sponsored cards inside categories.',
            'default_every_n' => 6,
            'default_max_per_page' => 3,
        ],
        self::SLOT_SEARCH_RESULTS => [
            'label' => 'Search Results Pages',
            'dimensions' => 'Native Card / 728x90',
            'device' => 'All Devices',
            'description' => 'High-intent promotional ads displayed in filtered search result grids.',
            'default_every_n' => 6,
            'default_max_per_page' => 3,
        ],
        self::SLOT_LOCATION_PAGE => [
            'label' => 'Location-Based Listing Pages',
            'dimensions' => 'Native Card / 728x90',
            'device' => 'All Devices',
            'description' => 'Geo-targeted local sponsors on State and City classifieds directory pages.',
            'default_every_n' => 6,
            'default_max_per_page' => 3,
        ],
        self::SLOT_SIDEBAR => [
            'label' => 'Sidebar Banner Advertisements',
            'dimensions' => '300x600 / 300x250',
            'device' => 'Desktop & Tablet',
            'description' => 'Persistent sidebar banner positions beside homepage, search, and detail feeds.',
            'default_every_n' => 1,
            'default_max_per_page' => 2,
        ],
        self::SLOT_LISTING_DETAIL => [
            'label' => 'Listing Details Page',
            'dimensions' => '728x90 / Responsive Card',
            'device' => 'All Devices',
            'description' => 'Promotional banner or affiliate offer card shown on individual listing pages.',
            'default_every_n' => 1,
            'default_max_per_page' => 2,
        ],
        self::SLOT_RELATED_LISTINGS => [
            'label' => 'Related Listings Section',
            'dimensions' => 'Native Card (3-col Grid)',
            'device' => 'All Devices',
            'description' => 'Sponsored offer blended cleanly alongside Similar Listings at detail page bottom.',
            'default_every_n' => 3,
            'default_max_per_page' => 1,
        ],
        self::SLOT_MOBILE_FEED => [
            'label' => 'Mobile Listing Feed',
            'dimensions' => '320x100 / Mobile Card',
            'device' => 'Mobile Only',
            'description' => 'Mobile-optimized promotional banners & cards served exclusively to mobile visitors.',
            'default_every_n' => 5,
            'default_max_per_page' => 3,
        ],
        self::SLOT_DESKTOP_FEED => [
            'label' => 'Desktop Listing Feed',
            'dimensions' => '970x250 / Desktop Card',
            'device' => 'Desktop & Tablet',
            'description' => 'Wide-format banners & native cards served exclusively to desktop/tablet viewports.',
            'default_every_n' => 6,
            'default_max_per_page' => 3,
        ],
        self::SLOT_HOMEPAGE_TOP => [
            'label' => 'Homepage Top Leaderboard',
            'dimensions' => '970x250 / 728x90',
            'device' => 'Desktop & Mobile',
            'description' => 'Pinned above-the-fold banner leaderboard across the homepage.',
            'default_every_n' => 1,
            'default_max_per_page' => 1,
        ],
        self::SLOT_FOOTER => [
            'label' => 'Sitewide Footer Leaderboard',
            'dimensions' => '970x90 / 728x90',
            'device' => 'All Devices',
            'description' => 'Bottom anchor promotional placement displayed above the global footer.',
            'default_every_n' => 1,
            'default_max_per_page' => 1,
        ],
    ];

    protected $fillable = [
        'uuid',
        'legacy_placement_id',
        'name',
        'campaign_type',
        'advertiser_name',
        'description',
        'destination_url',
        'internal_notes',
        'status',
        'priority',
        'weight',
        'price_cents',
        'daily_budget_cents',
        'total_budget_cents',
        'spent_cents',
        'cpc_cents',
        'cpm_cents',
        'max_impressions',
        'max_clicks',
        'daily_impression_cap',
        'daily_click_cap',
        'frequency_cap_per_session',
        'starts_at',
        'ends_at',
        'total_impressions',
        'total_unique_impressions',
        'total_clicks',
        'total_unique_clicks',
        'total_conversions',
        'total_conversion_revenue_cents',
        'postback_secret',
        'created_by_admin_id',
        'updated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'weight' => 'integer',
            'price_cents' => 'integer',
            'daily_budget_cents' => 'integer',
            'total_budget_cents' => 'integer',
            'spent_cents' => 'integer',
            'cpc_cents' => 'integer',
            'cpm_cents' => 'integer',
            'max_impressions' => 'integer',
            'max_clicks' => 'integer',
            'daily_impression_cap' => 'integer',
            'daily_click_cap' => 'integer',
            'frequency_cap_per_session' => 'integer',
            'total_impressions' => 'integer',
            'total_unique_impressions' => 'integer',
            'total_clicks' => 'integer',
            'total_unique_clicks' => 'integer',
            'total_conversions' => 'integer',
            'total_conversion_revenue_cents' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $campaign): void {
            if (empty($campaign->uuid)) {
                $campaign->uuid = (string) Str::uuid();
            }
            if (empty($campaign->postback_secret)) {
                $campaign->postback_secret = Str::random(40);
            }
        });

        static::saved(fn () => app(AdDeliveryService::class)->clearCache());
        static::deleted(fn () => app(AdDeliveryService::class)->clearCache());
        static::restored(fn () => app(AdDeliveryService::class)->clearCache());
    }

    public function creatives(): HasMany
    {
        return $this->hasMany(AdCreative::class, 'campaign_id');
    }

    public function primaryCreative(): HasOne
    {
        return $this->hasOne(AdCreative::class, 'campaign_id')
            ->where('is_active', true)
            ->orderByDesc('id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(AdCampaignPlacement::class, 'campaign_id');
    }

    public function targetingRules(): HasMany
    {
        return $this->hasMany(AdTargetingRule::class, 'campaign_id');
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(AdImpression::class, 'campaign_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AdClick::class, 'campaign_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(AdConversion::class, 'campaign_id');
    }

    public function dailyStatistics(): HasMany
    {
        return $this->hasMany(AdDailyStatistic::class, 'campaign_id');
    }

    public function legacyPlacement(): BelongsTo
    {
        return $this->belongsTo(AdPlacement::class, 'legacy_placement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function scopeEligibleForDelivery(Builder $query, ?Carbon $at = null): Builder
    {
        $now = $at ?? now();

        return $query
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_SCHEDULED])
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->where(fn (Builder $q) => $q->whereNull('total_budget_cents')->orWhereColumn('spent_cents', '<', 'total_budget_cents'))
            ->where(fn (Builder $q) => $q->whereNull('max_impressions')->orWhereColumn('total_impressions', '<', 'max_impressions'))
            ->where(fn (Builder $q) => $q->whereNull('max_clicks')->orWhereColumn('total_clicks', '<', 'max_clicks'));
    }

    public function isCurrentlyEligible(?Carbon $at = null): bool
    {
        $now = $at ?? now();

        if (!in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_SCHEDULED], true)) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->lte($now)) {
            return false;
        }
        if ($this->total_budget_cents !== null && $this->spent_cents >= $this->total_budget_cents) {
            return false;
        }
        if ($this->max_impressions !== null && $this->total_impressions >= $this->max_impressions) {
            return false;
        }
        if ($this->max_clicks !== null && $this->total_clicks >= $this->max_clicks) {
            return false;
        }

        return true;
    }

    public function clickThroughRate(): float
    {
        return $this->total_impressions > 0
            ? round(($this->total_clicks / $this->total_impressions) * 100, 2)
            : 0.0;
    }

    public function conversionRate(): float
    {
        return $this->total_clicks > 0
            ? round(($this->total_conversions / $this->total_clicks) * 100, 2)
            : 0.0;
    }

    public function duplicateCampaign(?int $adminId = null): self
    {
        return DB::transaction(function () use ($adminId) {
            $clone = $this->replicate([
                'uuid',
                'legacy_placement_id',
                'spent_cents',
                'total_impressions',
                'total_unique_impressions',
                'total_clicks',
                'total_unique_clicks',
                'total_conversions',
                'total_conversion_revenue_cents',
                'postback_secret',
            ]);
            $clone->uuid = (string) Str::uuid();
            $clone->name = $this->name . ' (Copy)';
            $clone->status = self::STATUS_DRAFT;
            $clone->spent_cents = 0;
            $clone->total_impressions = 0;
            $clone->total_unique_impressions = 0;
            $clone->total_clicks = 0;
            $clone->total_unique_clicks = 0;
            $clone->total_conversions = 0;
            $clone->total_conversion_revenue_cents = 0;
            $clone->postback_secret = Str::random(40);
            $clone->created_by_admin_id = $adminId;
            $clone->updated_by_admin_id = $adminId;
            $clone->save();

            foreach ($this->creatives as $creative) {
                $creativeClone = $creative->replicate();
                $creativeClone->campaign_id = $clone->id;
                $creativeClone->save();
            }

            foreach ($this->placements as $placement) {
                $placementClone = $placement->replicate();
                $placementClone->campaign_id = $clone->id;
                $placementClone->save();
            }

            foreach ($this->targetingRules as $rule) {
                $ruleClone = $rule->replicate();
                $ruleClone->campaign_id = $clone->id;
                $ruleClone->save();
            }

            return $clone;
        });
    }
}

