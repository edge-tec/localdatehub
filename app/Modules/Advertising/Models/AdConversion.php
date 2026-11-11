<?php

namespace App\Modules\Advertising\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdConversion extends Model
{
    public $timestamps = false;

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'conversion_token',
        'campaign_id',
        'click_id',
        'external_transaction_id',
        'sub_id',
        'payout_cents',
        'revenue_cents',
        'currency',
        'status',
        'verified_via',
        'metadata_json',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payout_cents' => 'integer',
            'revenue_cents' => 'integer',
            'metadata_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }

    public function click(): BelongsTo
    {
        return $this->belongsTo(AdClick::class, 'click_id');
    }
}

