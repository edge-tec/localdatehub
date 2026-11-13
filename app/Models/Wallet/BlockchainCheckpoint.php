<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainCheckpoint extends Model
{
    protected $table = 'blockchain_checkpoints';

    protected $fillable = [
        'network_id',
        'last_processed_height',
        'last_processed_hash',
        'last_processed_at',
    ];

    protected $casts = [
        'last_processed_height' => 'integer',
        'last_processed_at' => 'datetime',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }
}

