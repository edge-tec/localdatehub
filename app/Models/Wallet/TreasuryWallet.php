<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreasuryWallet extends Model
{
    protected $table = 'treasury_wallets';

    protected $fillable = [
        'network_id',
        'asset_id',
        'wallet_address',
        'custody_provider',
        'custody_account_reference',
        'is_allowlisted',
        'is_testnet',
        'status',
    ];

    protected $casts = [
        'is_allowlisted' => 'boolean',
        'is_testnet' => 'boolean',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'asset_id');
    }

    public function sweeps(): HasMany
    {
        return $this->hasMany(SweepTransaction::class, 'treasury_wallet_id');
    }
}

