<?php

namespace App\Modules\Payments\Crypto\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoInternalLedger extends Model
{
    use HasFactory;

    protected $table = 'crypto_internal_ledger';

    public const TYPE_DEPOSIT_CREDIT = 'DEPOSIT_CREDIT';
    public const TYPE_MONETIZATION_SPEND = 'MONETIZATION_SPEND';
    public const TYPE_REFUND = 'REFUND';
    public const TYPE_ADMIN_ADJUSTMENT = 'ADMIN_ADJUSTMENT';

    protected $fillable = [
        'user_id',
        'invoice_id',
        'entry_type',
        'amount_usd',
        'balance_before_usd',
        'balance_after_usd',
        'reference_code',
        'idempotency_key',
        'audit_notes',
    ];

    protected $casts = [
        'amount_usd' => 'decimal:2',
        'balance_before_usd' => 'decimal:2',
        'balance_after_usd' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CryptoPaymentInvoice::class, 'invoice_id');
    }
}

