<?php

namespace App\Modules\Payments\Crypto\Models;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use App\Modules\Monetization\Models\CreditPackage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoPaymentInvoice extends Model
{
    use HasFactory;

    protected $table = 'crypto_payment_invoices';

    public const STATUS_CREATED = 'CREATED';
    public const STATUS_WAITING_FOR_PAYMENT = 'WAITING_FOR_PAYMENT';
    public const STATUS_DETECTED = 'DETECTED';
    public const STATUS_CONFIRMING = 'CONFIRMING';
    public const STATUS_UNDERPAID = 'UNDERPAID';
    public const STATUS_OVERPAID = 'OVERPAID';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_MANUAL_REVIEW = 'MANUAL_REVIEW';
    public const STATUS_REFUNDED = 'REFUNDED';

    protected $fillable = [
        'invoice_number',
        'user_id',
        'deposit_address_id',
        'currency_symbol',
        'network_key',
        'fiat_amount_usd',
        'expected_crypto_amount',
        'received_crypto_amount',
        'exchange_rate_snapshot',
        'status',
        'required_confirmations',
        'current_confirmations',
        'tx_hash',
        'contract_address',
        'expires_at',
        'detected_at',
        'confirmed_at',
        'credited_at',
        'credit_package_id',
        'listing_id',
        'metadata',
    ];

    protected $casts = [
        'fiat_amount_usd' => 'decimal:2',
        'expected_crypto_amount' => 'decimal:8',
        'received_crypto_amount' => 'decimal:8',
        'exchange_rate_snapshot' => 'decimal:8',
        'required_confirmations' => 'integer',
        'current_confirmations' => 'integer',
        'expires_at' => 'datetime',
        'detected_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'credited_at' => 'datetime',
        'credit_package_id' => 'integer',
        'listing_id' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function depositAddress(): BelongsTo
    {
        return $this->belongsTo(CryptoDepositAddress::class, 'deposit_address_id');
    }

    public function creditPackage(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class, 'credit_package_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CryptoTransaction::class, 'invoice_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || ($this->expires_at && $this->expires_at->isPast() && !$this->isCompleted());
    }
}

