<?php

namespace App\Modules\Advertising\Models;

use App\Modules\Advertising\Services\AdDeliveryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdTargetingRule extends Model
{
    public const RULE_COUNTRY = 'country';
    public const RULE_STATE = 'state';
    public const RULE_CITY = 'city';
    public const RULE_ZIP_CODE = 'zip_code';
    public const RULE_CATEGORY = 'category';
    public const RULE_SUBCATEGORY = 'subcategory';
    public const RULE_DEVICE = 'device';

    public const RULE_TYPES = [
        self::RULE_COUNTRY,
        self::RULE_STATE,
        self::RULE_CITY,
        self::RULE_ZIP_CODE,
        self::RULE_CATEGORY,
        self::RULE_SUBCATEGORY,
        self::RULE_DEVICE,
    ];

    public const MODE_INCLUDE = 'include';
    public const MODE_EXCLUDE = 'exclude';

    protected $fillable = [
        'campaign_id',
        'rule_type',
        'rule_value',
        'match_mode',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => app(AdDeliveryService::class)->clearCache());
        static::deleted(fn () => app(AdDeliveryService::class)->clearCache());
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }
}

