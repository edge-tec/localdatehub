<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SigningRequest extends Model
{
    protected $table = 'signing_requests';

    protected $fillable = [
        'sweep_transaction_id',
        'network_id',
        'request_uuid',
        'intent_type',
        'from_address',
        'to_address',
        'amount_base_units',
        'nonce',
        'custody_provider',
        'status',
        'error_code',
    ];

    protected $casts = [
        'nonce' => 'integer',
    ];

    public function sweepTransaction(): BelongsTo
    {
        return $this->belongsTo(SweepTransaction::class, 'sweep_transaction_id');
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }
}

