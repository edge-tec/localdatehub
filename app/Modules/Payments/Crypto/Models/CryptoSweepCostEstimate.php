<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoSweepCostEstimate extends Model
{
    use HasFactory;

    protected $table = 'crypto_sweep_cost_estimates';

    protected $fillable = [
        'network_key',
        'currency_symbol',
        'token_balance',
        'estimated_gas_units',
        'estimated_gas_price',
        'estimated_fee_native',
        'estimated_fee_usd',
        'net_transfer_crypto',
        'is_profitable',
        'passes_admin_threshold',
        'calculated_at',
    ];

    protected $casts = [
        'token_balance' => 'decimal:8',
        'estimated_gas_units' => 'integer',
        'estimated_fee_native' => 'decimal:8',
        'estimated_fee_usd' => 'decimal:2',
        'net_transfer_crypto' => 'decimal:8',
        'is_profitable' => 'boolean',
        'passes_admin_threshold' => 'boolean',
        'calculated_at' => 'datetime',
    ];
}

