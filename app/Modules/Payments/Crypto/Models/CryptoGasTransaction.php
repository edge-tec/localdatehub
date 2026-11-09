<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoGasTransaction extends Model
{
    use HasFactory;

    protected $table = 'crypto_gas_transactions';

    protected $fillable = [
        'gas_wallet_id',
        'sweep_transaction_id',
        'network_key',
        'gas_currency',
        'fee_amount',
        'fee_usd',
        'tx_hash',
        'status',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:8',
        'fee_usd' => 'decimal:2',
    ];

    public function gasWallet(): BelongsTo
    {
        return $this->belongsTo(CryptoGasWallet::class, 'gas_wallet_id');
    }

    public function sweepTransaction(): BelongsTo
    {
        return $this->belongsTo(CryptoSweepTransaction::class, 'sweep_transaction_id');
    }
}

