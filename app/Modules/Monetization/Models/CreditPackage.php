<?php

namespace App\Modules\Monetization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditPackage extends Model
{
    use HasFactory;

    protected $table = 'credit_packages';

    protected $fillable = [
        'name',
        'credits',
        'bonus_credits',
        'price_usd',
        'display_order',
        'is_popular',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'bonus_credits' => 'integer',
            'price_usd' => 'decimal:2',
            'display_order' => 'integer',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getTotalCreditsAttribute(): int
    {
        return $this->credits + $this->bonus_credits;
    }
}

