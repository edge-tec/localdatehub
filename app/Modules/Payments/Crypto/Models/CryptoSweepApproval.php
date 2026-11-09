<?php

namespace App\Modules\Payments\Crypto\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoSweepApproval extends Model
{
    use HasFactory;

    protected $table = 'crypto_sweep_approvals';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'sweep_transaction_id',
        'admin_id',
        'requested_amount_usd',
        'requested_crypto_amount',
        'network_key',
        'destination_address',
        'estimated_fee_usd',
        'status',
        'decision_reason',
        'decided_at',
    ];

    protected $casts = [
        'requested_amount_usd' => 'decimal:2',
        'requested_crypto_amount' => 'decimal:8',
        'estimated_fee_usd' => 'decimal:2',
        'decided_at' => 'datetime',
    ];

    public function sweepTransaction(): BelongsTo
    {
        return $this->belongsTo(CryptoSweepTransaction::class, 'sweep_transaction_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}

