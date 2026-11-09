<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoostPlan extends Model
{
    use HasFactory;

    protected $table = 'boost_plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'duration_days',
        'featured_position',
        'priority_level',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'priority_level' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(CryptoPayment::class, 'boost_plan_id');
    }

    public function boosts(): HasMany
    {
        return $this->hasMany(ListingBoost::class, 'boost_plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }

    /**
     * Get formatted price string.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format((float) $this->price, 2) . ' ' . $this->currency;
    }
}

