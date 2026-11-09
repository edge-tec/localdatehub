<?php

namespace App\Modules\Payments\Crypto\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoGasFundingRequest extends Model
{
    use HasFactory;

    protected $table = 'crypto_gas_funding_requests';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_EXECUTED = 'EXECUTED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'gas_wallet_id',
        'admin_id',
        'requested_amount_native',
        'network_key',
        'status',
        'funding_tx_hash',
        'notes',
    ];

    protected $casts = [
        'requested_amount_native' => 'decimal:8',
    ];

    public function gasWallet(): BelongsTo
    {
        return $this->belongsTo(CryptoGasWallet::class, 'gas_wallet_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}

