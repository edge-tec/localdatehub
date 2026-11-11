<?php

namespace App\Modules\Advertising\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdImpression extends Model
{
    public $timestamps = false;

    protected $fillable = [
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
        'page_url',
        'is_unique',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_unique' => 'boolean',
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
}

