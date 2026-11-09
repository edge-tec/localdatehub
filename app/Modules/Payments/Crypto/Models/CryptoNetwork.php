<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoNetwork extends Model
{
    use HasFactory;

    protected $table = 'crypto_networks';

    protected $fillable = [
        'currency_id',
        'network_key',
        'chain_name',
        'chain_id',
        'contract_address',
        'gas_currency',
        'required_confirmations',
        'is_testnet',
        'is_active',
        'rpc_endpoint_primary',
        'rpc_endpoint_fallback',
        'explorer_tx_url',
        'explorer_address_url',
        'network_fee_estimate_usd',
        'payment_timeout_minutes',
    ];

    protected $casts = [
        'chain_id' => 'integer',
        'required_confirmations' => 'integer',
        'is_testnet' => 'boolean',
        'is_active' => 'boolean',
        'network_fee_estimate_usd' => 'decimal:2',
        'payment_timeout_minutes' => 'integer',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CryptoCurrency::class, 'currency_id');
    }

    public function depositAddresses(): HasMany
    {
        return $this->hasMany(CryptoDepositAddress::class, 'network_id');
    }

    public function isToken(): bool
    {
        return !empty($this->contract_address);
    }
}

