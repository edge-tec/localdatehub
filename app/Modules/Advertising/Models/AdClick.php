<?php

namespace App\Modules\Advertising\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'click_token',
        'campaign_id',
        'creative_id',
        'placement_slot',
        'visitor_hash',
        'session_id',
        'user_id',
        'ip_hash',
        'user_agent',
        'device_type',
        'country_code',
        'state_code',
        'city_id',
        'category_id',
        'destination_url',
        'is_unique',
        'is_suspicious',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_unique' => 'boolean',
            'is_suspicious' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }

    public function creative(): BelongsTo
    {
        return $this->belongsTo(AdCreative::class, 'creative_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversion(): HasOne
    {
        return $this->hasOne(AdConversion::class, 'click_id');
    }
}

