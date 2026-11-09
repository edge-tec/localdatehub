<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoCurrency extends Model
{
    use HasFactory;

    protected $table = 'crypto_currencies';

    protected $fillable = [
        'name',
        'symbol',
        'is_active',
        'decimal_precision',
        'min_deposit_usd',
        'max_deposit_usd',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'decimal_precision' => 'integer',
        'min_deposit_usd' => 'decimal:2',
        'max_deposit_usd' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function networks(): HasMany
    {
        return $this->hasMany(CryptoNetwork::class, 'currency_id');
    }

    public function activeNetworks(): HasMany
    {
        return $this->hasMany(CryptoNetwork::class, 'currency_id')->where('is_active', true);
    }
}

