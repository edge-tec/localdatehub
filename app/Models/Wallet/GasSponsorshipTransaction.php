<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GasSponsorshipTransaction extends Model
{
    protected $table = 'gas_sponsorship_transactions';

    protected $fillable = [
        'network_id',
        'deposit_id',
        'target_address',
        'amount_base_units',
        'transaction_hash',
        'status',
        'error_code',
        'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class, 'deposit_id');
    }
}

