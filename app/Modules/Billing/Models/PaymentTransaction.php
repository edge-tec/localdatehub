<?php

namespace App\Modules\Billing\Models;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pricing_plan_id',
        'listing_id',
        'reference_id',
        'amount_cents',
        'currency',
        'payment_method',
        'gateway',
        'gateway_transaction_id',
        'status',
        'gateway_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'gateway_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
