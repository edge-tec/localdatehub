<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SweepTransaction extends Model
{
    protected $table = 'sweep_transactions';

    protected $fillable = [
        'deposit_id',
        'treasury_wallet_id',
        'network_id',
        'transaction_hash',
        'explorer_url',
        'nonce',
        'amount_base_units',
        'network_fee_base_units',
        'gas_sponsored_base_units',
        'status',
        'attempt_count',
        'last_error_code',
        'broadcast_at',
        'confirmed_at',
    ];

    protected $casts = [
        'nonce' => 'integer',
        'attempt_count' => 'integer',
        'broadcast_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class, 'deposit_id');
    }

    public function treasuryWallet(): BelongsTo
    {
        return $this->belongsTo(TreasuryWallet::class, 'treasury_wallet_id');
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }
}

