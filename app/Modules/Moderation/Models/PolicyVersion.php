<?php

namespace App\Modules\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyVersion extends Model
{
    public const TYPE_TERMS = 'terms_of_service';
    public const TYPE_TRUST_SAFETY = 'trust_and_safety';
    public const TYPE_PRIVACY = 'privacy_policy';
    public const TYPE_DMCA = 'dmca_policy';
    public const TYPE_AGE_ASSURANCE = 'age_assurance_policy';
    public const TYPE_CONTENT_GUIDELINES = 'content_guidelines';

    protected $fillable = [
        'policy_type',
        'version',
        'title',
        'summary_of_changes',
        'content',
        'is_active',
        'effective_at',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'effective_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function current(string $type): ?self
    {
        return static::where('policy_type', $type)->where('is_active', true)->latest('effective_at')->first();
    }
}
