<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoTransaction extends Model
{
    use HasFactory;

    protected $table = 'crypto_transactions';

    protected $fillable = [
        'invoice_id',
        'deposit_address_id',
        'tx_hash',
        'block_height',
        'from_address',
        'to_address',
        'token_contract',
        'amount',
        'confirmations',
        'is_valid',
        'validation_errors',
        'detected_at',
        'confirmed_at',
    ];

    protected $casts = [
        'block_height' => 'integer',
        'amount' => 'decimal:8',
        'confirmations' => 'integer',
        'is_valid' => 'boolean',
        'validation_errors' => 'array',
        'detected_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CryptoPaymentInvoice::class, 'invoice_id');
    }

    public function depositAddress(): BelongsTo
    {
        return $this->belongsTo(CryptoDepositAddress::class, 'deposit_address_id');
    }

    public function confirmationHistory(): HasMany
    {
        return $this->hasMany(CryptoPaymentConfirmation::class, 'crypto_transaction_id');
    }
}

