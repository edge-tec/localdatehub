<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoAsset extends Model
{
    protected $table = 'crypto_assets';

    protected $fillable = [
        'network_id',
        'symbol',
        'name',
        'contract_address',
        'decimals',
        'asset_type',
        'minimum_deposit',
        'is_enabled',
    ];

    protected $casts = [
        'decimals' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }
}

