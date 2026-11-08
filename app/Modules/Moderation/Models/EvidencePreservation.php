<?php

namespace App\Modules\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EvidencePreservation extends Model
{
    protected $fillable = [
        'preservable_type',
        'preservable_id',
        'legal_request_id',
        'hold_reason',
        'snapshot_data',
        'expires_at',
        'is_active',
        'frozen_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function legalRequest(): BelongsTo
    {
        return $this->belongsTo(LegalRequest::class, 'legal_request_id');
    }

    public function frozenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'frozen_by_admin_id');
    }

    public function preservable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if a specific model is currently under an active evidence hold.
     */
    public static function isUnderHold(Model $model): bool
    {
        return static::where('preservable_type', get_class($model))
            ->where('preservable_id', $model->getKey())
            ->active()
            ->exists();
    }
}

