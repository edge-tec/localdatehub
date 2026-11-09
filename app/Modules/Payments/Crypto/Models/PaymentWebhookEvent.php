<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'payment_webhook_events';

    protected $fillable = [
        'gateway_id',
        'crypto_payment_id',
        'provider_event_id',
        'event_type',
        'payload',
        'headers',
        'signature_verified',
        'processing_status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'signature_verified' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CryptoPayment::class, 'crypto_payment_id');
    }
}

