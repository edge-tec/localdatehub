<?php

namespace App\Modules\Payments\Crypto\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CryptoSweepTransaction extends Model
{
    use HasFactory;

    protected $table = 'crypto_sweep_transactions';

    public const TYPE_AUTOMATIC_SMALL = 'automatic_small';
    public const TYPE_ADMIN_APPROVED_LARGE = 'admin_approved_large';
    public const TYPE_MANUAL_ADMIN = 'manual_admin';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_AWAITING_APPROVAL = 'AWAITING_APPROVAL';
    public const STATUS_SIGNING = 'SIGNING';
    public const STATUS_BROADCASTED = 'BROADCASTED';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'invoice_id',
        'source_address',
        'destination_address',
        'destination_treasury_tier',
        'currency_symbol',
        'network_key',
        'amount',
        'amount_usd',
        'estimated_gas_cost',
        'actual_gas_cost',
        'gas_currency',
        'gas_tx_hash',
        'sweep_tx_hash',
        'sweep_type',
        'status',
        'approved_by',
        'approved_at',
        'executed_by',
        'executed_at',
        'confirmed_at',
        'error_message',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'amount_usd' => 'decimal:2',
        'estimated_gas_cost' => 'decimal:8',
        'actual_gas_cost' => 'decimal:8',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CryptoPaymentInvoice::class, 'invoice_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function approval(): HasOne
    {
        return $this->hasOne(CryptoSweepApproval::class, 'sweep_transaction_id');
    }

    public function signingRequest(): HasOne
    {
        return $this->hasOne(CryptoSigningRequest::class, 'sweep_transaction_id');
    }
}

