<?php

namespace App\Modules\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentFraudLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_transaction_id',
        'ip_address',
        'card_fingerprint_hash',
        'risk_score',
        'risk_tier',
        'flags',
        'action_taken',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'integer',
            'flags' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}