<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoTreasuryWallet extends Model
{
    use HasFactory;

    protected $table = 'crypto_treasury_wallets';

    public const TIER_TEMPORARY_TRUST_WALLET = 'temporary_trust_wallet';
    public const TIER_MAIN_TRUST_WALLET = 'main_trust_wallet';

    protected $fillable = [
        'wallet_name',
        'tier',
        'network_key',
        'public_receiving_address',
        'current_balance',
        'available_balance',
        'pending_deposits',
        'total_received',
        'total_transferred',
        'is_active',
    ];

    protected $casts = [
        'current_balance' => 'decimal:8',
        'available_balance' => 'decimal:8',
        'pending_deposits' => 'decimal:8',
        'total_received' => 'decimal:8',
        'total_transferred' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function isTemporary(): bool
    {
        return $this->tier === self::TIER_TEMPORARY_TRUST_WALLET;
    }

    public function isMain(): bool
    {
        return $this->tier === self::TIER_MAIN_TRUST_WALLET;
    }
}

