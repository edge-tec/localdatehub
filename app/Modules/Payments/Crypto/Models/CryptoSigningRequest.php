<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoSigningRequest extends Model
{
    use HasFactory;

    protected $table = 'crypto_signing_requests';

    public $timestamps = false;

    protected $fillable = [
        'sweep_transaction_id',
        'payload_hash',
        'signing_status',
        'signed_payload',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function sweepTransaction(): BelongsTo
    {
        return $this->belongsTo(CryptoSweepTransaction::class, 'sweep_transaction_id');
    }
}

