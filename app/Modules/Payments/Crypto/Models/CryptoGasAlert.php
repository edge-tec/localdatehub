<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoGasAlert extends Model
{
    use HasFactory;

    protected $table = 'crypto_gas_alerts';

    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_CRITICAL = 'CRITICAL';
    public const LEVEL_EMERGENCY = 'EMERGENCY';

    protected $fillable = [
        'network_key',
        'gas_currency',
        'alert_level',
        'message',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];
}

