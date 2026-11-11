<?php

namespace App\Modules\Advertising\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdDailyStatistic extends Model
{
    protected $fillable = [
        'date',
        'campaign_id',
        'placement_slot',
        'device_type',
        'state_code',
        'impressions',
        'unique_impressions',
        'clicks',
        'unique_clicks',
        'conversions',
        'revenue_cents',
        'spend_cents',
    ];

    protected function casts(): array
    {
        return [
            'impressions' => 'integer',
            'unique_impressions' => 'integer',
            'clicks' => 'integer',
            'unique_clicks' => 'integer',
            'conversions' => 'integer',
            'revenue_cents' => 'integer',
            'spend_cents' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }
}
