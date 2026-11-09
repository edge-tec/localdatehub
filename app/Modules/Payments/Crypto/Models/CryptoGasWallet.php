<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoGasWallet extends Model
{
    use HasFactory;

    protected $table = 'crypto_gas_wallets';

    protected $fillable = [
        'network_key',
        'gas_currency',
        'public_address',
        'balance_native',
        'balance_usd',
        'is_active',
    ];

    protected $casts = [
        'balance_native' => 'decimal:8',
        'balance_usd' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(CryptoGasTransaction::class, 'gas_wallet_id');
    }

    public function fundingRequests(): HasMany
    {
        return $this->hasMany(CryptoGasFundingRequest::class, 'gas_wallet_id');
    }
}

