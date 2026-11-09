<?php

namespace App\Modules\Payments\Crypto\Models;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CryptoPayment extends Model
{
    use HasFactory;

    protected $table = 'crypto_payments';

    public const STATUS_CREATED = 'CREATED';
    public const STATUS_AWAITING_PAYMENT = 'AWAITING_PAYMENT';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const STATUS_CONFIRMING = 'CONFIRMING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_UNDERPAID = 'UNDERPAID';
    public const STATUS_OVERPAID = 'OVERPAID';
    public const STATUS_REFUND_PENDING = 'REFUND_PENDING';
    public const STATUS_REFUNDED = 'REFUNDED';
    public const STATUS_MANUAL_REVIEW = 'MANUAL_REVIEW';

    protected $fillable = [
        'user_id',
        'payment_type',
        'listing_id',
        'boost_plan_id',
        'credit_package_id',
        'gateway_id',
        'internal_order_id',
        'provider_payment_id',
        'provider_invoice_id',
        'payment_currency',
        'payment_network',
        'expected_amount',
        'received_amount',
        'fiat_amount',
        'fiat_currency',
        'transaction_hash',
        'payment_address',
        'payment_url',
        'qr_code_data',
        'confirmations',
        'required_confirmations',
        'status',
        'expires_at',
        'confirmed_at',
        'metadata',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:8',
        'received_amount' => 'decimal:8',
        'fiat_amount' => 'decimal:2',
        'confirmations' => 'integer',
        'required_confirmations' => 'integer',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function boostPlan(): BelongsTo
    {
        return $this->belongsTo(BoostPlan::class);
    }

    public function creditPackage(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Monetization\Models\CreditPackage::class, 'credit_package_id');
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }

    public function boost(): HasOne
    {
        return $this->hasOne(ListingBoost::class, 'payment_id');
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(PaymentWebhookEvent::class, 'crypto_payment_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || ($this->expires_at && $this->expires_at->isPast() && !$this->isCompleted());
    }

    public function isConfirming(): bool
    {
        return $this->status === self::STATUS_CONFIRMING;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_CREATED,
            self::STATUS_AWAITING_PAYMENT,
            self::STATUS_PENDING,
            self::STATUS_CONFIRMING,
        ], true);
    }

    /**
     * Determine if payment is eligible to activate a post boost.
     */
    public function canActivateBoost(): bool
    {
        return $this->isCompleted() && !$this->boost()->exists();
    }

    /**
     * Get explorer link for transaction hash if available.
     */
    public function getExplorerUrl(): ?string
    {
        if (empty($this->transaction_hash)) {
            return null;
        }

        $hash = $this->transaction_hash;
        $curr = strtoupper($this->payment_currency);
        $net = strtoupper($this->payment_network);

        if ($curr === 'BTC' || $net === 'BITCOIN') {
            return "https://mempool.space/tx/{$hash}";
        }
        if ($curr === 'ETH' || $net === 'ERC20' || $net === 'ETHEREUM') {
            return "https://etherscan.io/tx/{$hash}";
        }
        if ($net === 'TRC20' || $curr === 'TRX') {
            return "https://tronscan.org/#/transaction/{$hash}";
        }
        if ($net === 'BEP20' || $curr === 'BNB') {
            return "https://bscscan.com/tx/{$hash}";
        }
        if ($net === 'POLYGON' || $curr === 'MATIC') {
            return "https://polygonscan.com/tx/{$hash}";
        }
        if ($net === 'SOLANA' || $curr === 'SOL') {
            return "https://solscan.io/tx/{$hash}";
        }
        if ($curr === 'LTC') {
            return "https://blockchair.com/litecoin/transaction/{$hash}";
        }
        if ($curr === 'DOGE') {
            return "https://dogechain.info/tx/{$hash}";
        }

        return null;
    }
}

