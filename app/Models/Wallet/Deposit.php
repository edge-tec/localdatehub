<?php

namespace App\Models\Wallet;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Deposit extends Model
{
    public const STATUS_DETECTED = 'DETECTED';
    public const STATUS_CONFIRMING = 'CONFIRMING';
    public const STATUS_VERIFIED = 'VERIFIED';
    public const STATUS_CREDITED = 'CREDITED';
    public const STATUS_SWEEP_QUEUED = 'SWEEP_QUEUED';
    public const STATUS_SWEEPING = 'SWEEPING';
    public const STATUS_SWEPT = 'SWEPT';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_REORGED = 'REORGED';
    public const STATUS_RECOVERY_REQUIRED = 'RECOVERY_REQUIRED';

    protected $table = 'deposits';

    protected $fillable = [
        'user_id',
        'wallet_address_id',
        'blockchain_transaction_id',
        'asset_id',
        'network_id',
        'amount_base_units',
        'credited_amount_base_units',
        'status',
        'credited_at',
    ];

    protected $casts = [
        'credited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletAddress(): BelongsTo
    {
        return $this->belongsTo(WalletAddress::class, 'wallet_address_id');
    }

    public function blockchainTransaction(): BelongsTo
    {
        return $this->belongsTo(BlockchainTransaction::class, 'blockchain_transaction_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'asset_id');
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }

    public function sweepTransaction(): HasOne
    {
        return $this->hasOne(SweepTransaction::class, 'deposit_id');
    }
}

