<?php

namespace App\Models\Wallet;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletAddress extends Model
{
    protected $table = 'wallet_addresses';

    protected $fillable = [
        'user_id',
        'network_id',
        'asset_id',
        'wallet_account_id',
        'address',
        'derivation_path',
        'derivation_index',
        'address_type',
        'status',
    ];

    protected $casts = [
        'derivation_index' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'asset_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WalletAccount::class, 'wallet_account_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class, 'wallet_address_id');
    }
}

