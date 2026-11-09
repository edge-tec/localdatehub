<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoGasReserve extends Model
{
    use HasFactory;

    protected $table = 'crypto_gas_reserves';

    protected $fillable = [
        'network_key',
        'gas_currency',
        'current_balance',
        'min_reserve_threshold',
        'emergency_reserve_threshold',
        'target_reserve',
        'replenishment_threshold',
        'daily_spending_limit',
        'spent_today',
        'is_alert_active',
        'last_replenished_at',
    ];

    protected $casts = [
        'current_balance' => 'decimal:8',
        'min_reserve_threshold' => 'decimal:8',
        'emergency_reserve_threshold' => 'decimal:8',
        'target_reserve' => 'decimal:8',
        'replenishment_threshold' => 'decimal:8',
        'daily_spending_limit' => 'decimal:8',
        'spent_today' => 'decimal:8',
        'is_alert_active' => 'boolean',
        'last_replenished_at' => 'datetime',
    ];

    public function isSufficientFor(float $estimatedFee): bool
    {
        return ($this->current_balance - $estimatedFee) >= $this->emergency_reserve_threshold;
    }

    public function isBelowReplenishmentThreshold(): bool
    {
        return $this->current_balance <= $this->replenishment_threshold;
    }
}

