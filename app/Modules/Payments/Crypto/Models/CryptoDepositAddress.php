<?php

namespace App\Modules\Payments\Crypto\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoDepositAddress extends Model
{
    use HasFactory;

    protected $table = 'crypto_deposit_addresses';

    protected $fillable = [
        'user_id',
        'currency_id',
        'network_id',
        'public_address',
        'derivation_index',
        'derivation_path',
        'is_assigned',
        'assigned_invoice_id',
        'last_detected_at',
    ];

    protected $casts = [
        'derivation_index' => 'integer',
        'is_assigned' => 'boolean',
        'assigned_invoice_id' => 'integer',
        'last_detected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CryptoCurrency::class, 'currency_id');
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(CryptoNetwork::class, 'network_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CryptoPaymentInvoice::class, 'deposit_address_id');
    }
}

