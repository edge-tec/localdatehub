<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BlockchainTransaction extends Model
{
    protected $table = 'blockchain_transactions';

    protected $fillable = [
        'network_id',
        'transaction_hash',
        'transaction_index',
        'block_height',
        'block_hash',
        'sender_address',
        'recipient_address',
        'asset_id',
        'amount_base_units',
        'confirmation_count',
        'transaction_status',
        'raw_transaction_reference',
        'detected_at',
        'verified_at',
    ];

    protected $casts = [
        'transaction_index' => 'integer',
        'block_height' => 'integer',
        'confirmation_count' => 'integer',
        'detected_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'asset_id');
    }

    public function deposit(): HasOne
    {
        return $this->hasOne(Deposit::class, 'blockchain_transaction_id');
    }
}

