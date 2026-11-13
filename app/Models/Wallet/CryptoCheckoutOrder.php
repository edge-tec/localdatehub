<?php

namespace App\Models\Wallet;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoCheckoutOrder extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CONFIRMING = 'CONFIRMING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_OVERPAID = 'OVERPAID';
    public const STATUS_UNDERPAID = 'UNDERPAID';
    public const STATUS_EXPIRED = 'EXPIRED';

    protected $table = 'crypto_checkout_orders';

    protected $fillable = [
        'order_reference',
        'user_id',
        'network_id',
        'asset_id',
        'wallet_address_id',
        'deposit_id',
        'title',
        'expected_amount_base_units',
        'received_amount_base_units',
        'status',
        'expires_at',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(BlockchainNetwork::class, 'network_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'asset_id');
    }

    public function walletAddress(): BelongsTo
    {
        return $this->belongsTo(WalletAddress::class, 'wallet_address_id');
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class, 'deposit_id');
    }
}
