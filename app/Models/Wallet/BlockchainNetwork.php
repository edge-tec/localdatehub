<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BlockchainNetwork extends Model
{
    protected $table = 'blockchain_networks';

    protected $fillable = [
        'name',
        'network_code',
        'chain_id',
        'network_type',
        'is_testnet',
        'rpc_configuration_reference',
        'explorer_base_url',
        'confirmation_threshold',
        'is_enabled',
    ];

    protected $casts = [
        'chain_id' => 'integer',
        'is_testnet' => 'boolean',
        'confirmation_threshold' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(CryptoAsset::class, 'network_id');
    }

    public function checkpoint(): HasOne
    {
        return $this->hasOne(BlockchainCheckpoint::class, 'network_id');
    }

    public function treasuryWallets(): HasMany
    {
        return $this->hasMany(TreasuryWallet::class, 'network_id');
    }
}

