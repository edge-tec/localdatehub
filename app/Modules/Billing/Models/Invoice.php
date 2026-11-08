<?php

namespace App\Modules\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_transaction_id',
        'invoice_number',
        'status',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'currency',
        'customer_name',
        'customer_email',
        'billing_address',
        'line_items',
        'paid_at',
        'due_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'billing_address' => 'array',
            'line_items' => 'array',
            'paid_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total_cents / 100, 2);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal_cents / 100, 2);
    }

    public function getFormattedTaxAttribute(): string
    {
        return '$' . number_format($this->tax_cents / 100, 2);
    }
}