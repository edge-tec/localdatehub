<?php

namespace App\Modules\Advertising\Models;

use App\Modules\Advertising\Services\AdDeliveryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdCreative extends Model
{
    public const FORMAT_BANNER = 'banner';
    public const FORMAT_AFFILIATE = 'affiliate';

    public const DISCLOSURE_LABELS = [
        'Sponsored',
        'Advertisement',
        'Promoted',
        'Affiliate Offer',
    ];

    protected $fillable = [
        'campaign_id',
        'format',
        'title',
        'short_description',
        'cta_text',
        'destination_url',
        'desktop_banner_url',
        'mobile_banner_url',
        'thumbnail_url',
        'alt_text',
        'affiliate_brand_name',
        'affiliate_tracking_url',
        'offer_badge',
        'disclosure_label',
        'offer_expires_at',
        'subid_param',
        'click_id_param',
        'extra_tracking_params',
        'html_snippet',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'offer_expires_at' => 'datetime',
            'extra_tracking_params' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(AdDeliveryService::class)->clearCache());
        static::deleted(fn () => app(AdDeliveryService::class)->clearCache());
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }

    public function resolvedImageUrl(string $deviceType = 'desktop'): ?string
    {
        if ($deviceType === 'mobile' && !empty($this->mobile_banner_url)) {
            return $this->mobile_banner_url;
        }

        return $this->desktop_banner_url ?: $this->thumbnail_url ?: $this->mobile_banner_url;
    }

    public function resolvedTargetUrl(): ?string
    {
        if ($this->format === self::FORMAT_AFFILIATE && !empty($this->affiliate_tracking_url)) {
            return $this->affiliate_tracking_url;
        }

        return $this->destination_url ?: $this->affiliate_tracking_url ?: $this->campaign?->destination_url;
    }
}

