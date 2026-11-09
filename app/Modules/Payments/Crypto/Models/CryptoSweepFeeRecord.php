<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoSweepFeeRecord extends Model
{
    use HasFactory;

    protected $table = 'crypto_sweep_fee_records';

    protected $fillable = [
        'sweep_transaction_id',
        'estimated_fee_native',
        'actual_fee_native',
        'variance_native',
        'estimated_fee_usd',
        'actual_fee_usd',
        'reconciled_at',
    ];

    protected $casts = [
        'estimated_fee_native' => 'decimal:8',
        'actual_fee_native' => 'decimal:8',
        'variance_native' => 'decimal:8',
        'estimated_fee_usd' => 'decimal:2',
        'actual_fee_usd' => 'decimal:2',
        'reconciled_at' => 'datetime',
    ];

    public function sweepTransaction(): BelongsTo
    {
        return $this->belongsTo(CryptoSweepTransaction::class, 'sweep_transaction_id');
    }
}

