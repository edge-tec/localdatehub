<?php

namespace App\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway',
        'event_id',
        'event_type',
        'payload',
        'is_processed',
        'processed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'is_processed' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
