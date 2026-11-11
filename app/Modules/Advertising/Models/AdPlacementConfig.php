<?php

namespace App\Modules\Advertising\Models;

use App\Modules\Advertising\Services\AdDeliveryService;
use Illuminate\Database\Eloquent\Model;

class AdPlacementConfig extends Model
{
    public const ROTATION_PRIORITY_WEIGHTED = 'priority_weighted';
    public const ROTATION_RANDOM = 'random';
    public const ROTATION_ROUND_ROBIN = 'round_robin';

    protected $fillable = [
        'slot',
        'label',
        'description',
        'insert_every_n',
        'max_ads_per_page',
        'rotation_mode',
        'dedupe_session',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'insert_every_n' => 'integer',
            'max_ads_per_page' => 'integer',
            'dedupe_session' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(AdDeliveryService::class)->clearCache());
        static::deleted(fn () => app(AdDeliveryService::class)->clearCache());
    }

    public static function forSlot(string $slot): self
    {
        $catalog = AdCampaign::SLOT_CATALOG[$slot] ?? null;

        return static::firstOrCreate(
            ['slot' => $slot],
            [
                'label' => $catalog['label'] ?? ucwords(str_replace('_', ' ', $slot)),
                'description' => $catalog['description'] ?? null,
                'insert_every_n' => $catalog['default_every_n'] ?? 6,
                'max_ads_per_page' => $catalog['default_max_per_page'] ?? 3,
                'rotation_mode' => self::ROTATION_PRIORITY_WEIGHTED,
                'dedupe_session' => true,
                'is_enabled' => true,
            ]
        );
    }
}

