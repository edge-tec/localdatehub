<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Model;

class RecoveryQueueItem extends Model
{
    protected $table = 'recovery_queue';

    protected $fillable = [
        'operation_type',
        'reference_id',
        'failure_code',
        'retry_count',
        'next_retry_at',
        'status',
    ];

    protected $casts = [
        'reference_id' => 'integer',
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
    ];
}

