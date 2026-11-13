<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GasReserve extends Model
{
    const CREATED_AT = null;

    protected $table = 'gas_reserves';

    protected $fillable = [
        'network_id',
        'asset_id',
        'treasury_wallet_id',
        'available_balance_base_units',
        'minimum_reserve_base_units',
        'status',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'asset_id');
    }

    public function treasuryWallet(): BelongsTo
    {
        return $this->belongsTo(TreasuryWallet::class, 'treasury_wallet_id');
    }
}

