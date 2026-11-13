<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    public $timestamps = false;

    protected $table = 'webhook_events';

    protected $fillable = [
        'provider',
        'event_id',
        'network_id',
        'payload_reference',
        'verification_status',
        'processed_at',
        'created_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }
}

