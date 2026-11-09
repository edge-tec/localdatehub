<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoExchangeRate extends Model
{
    use HasFactory;

    protected $table = 'crypto_exchange_rates';

    protected $fillable = [
        'currency_symbol',
        'fiat_currency',
        'rate',
        'rate_source',
        'markup_percentage',
        'is_manual_override',
        'expires_at',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'markup_percentage' => 'decimal:2',
        'is_manual_override' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}

