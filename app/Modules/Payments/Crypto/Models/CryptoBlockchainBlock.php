<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoBlockchainBlock extends Model
{
    use HasFactory;

    protected $table = 'crypto_blockchain_blocks';

    protected $fillable = [
        'network_key',
        'last_scanned_block',
        'highest_scanned_block',
        'is_scanning',
        'last_scanned_at',
    ];

    protected $casts = [
        'last_scanned_block' => 'integer',
        'highest_scanned_block' => 'integer',
        'is_scanning' => 'boolean',
        'last_scanned_at' => 'datetime',
    ];
}

