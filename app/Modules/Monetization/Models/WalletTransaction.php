<?php

namespace App\Modules\Monetization\Models;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $table = 'wallet_transactions';

    public const TYPE_CREDIT_PURCHASE = 'CREDIT_PURCHASE';
    public const TYPE_BONUS_CREDIT = 'BONUS_CREDIT';
    public const TYPE_POST_CREATION_DEBIT = 'POST_CREATION_DEBIT';
    public const TYPE_POST_BOOST_DEBIT = 'POST_BOOST_DEBIT';
    public const TYPE_POST_EDIT_DEBIT = 'POST_EDIT_DEBIT';
    public const TYPE_REFUND = 'REFUND';
    public const TYPE_ADMIN_CREDIT = 'ADMIN_CREDIT';
    public const TYPE_ADMIN_DEBIT = 'ADMIN_DEBIT';
    public const TYPE_PROMOTIONAL_CREDIT = 'PROMOTIONAL_CREDIT';
    public const TYPE_TRANSACTION_REVERSAL = 'TRANSACTION_REVERSAL';

    protected $fillable = [
        'user_id',
        'reference',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'listing_id',
        'crypto_payment_id',
        'idempotency_key',
        'description',
        'actor_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function cryptoPayment(): BelongsTo
    {
        return $this->belongsTo(CryptoPayment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }
}

