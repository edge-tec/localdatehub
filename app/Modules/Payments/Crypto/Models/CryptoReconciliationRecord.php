<?php

namespace App\Modules\Payments\Crypto\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoReconciliationRecord extends Model
{
    use HasFactory;

    protected $table = 'crypto_reconciliation_records';

    protected $fillable = [
        'reconciliation_date',
        'total_deposits_detected',
        'total_credits_granted',
        'total_funds_swept',
        'total_network_fees_paid',
        'discrepancies_count',
        'summary_json',
        'reconciled_by',
        'reconciled_at',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'total_deposits_detected' => 'decimal:2',
        'total_credits_granted' => 'decimal:2',
        'total_funds_swept' => 'decimal:2',
        'total_network_fees_paid' => 'decimal:2',
        'discrepancies_count' => 'integer',
        'summary_json' => 'array',
        'reconciled_at' => 'datetime',
    ];

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}

