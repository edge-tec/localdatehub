<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoPaymentConfirmation extends Model
{
    use HasFactory;

    protected $table = 'crypto_payment_confirmations';

    protected $fillable = [
        'crypto_transaction_id',
        'block_number',
        'block_hash',
        'confirmations_count',
        'verified_at',
    ];

    protected $casts = [
        'block_number' => 'integer',
        'confirmations_count' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(CryptoTransaction::class, 'crypto_transaction_id');
    }
}

