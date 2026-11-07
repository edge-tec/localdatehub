<?php

namespace App\Modules\Moderation\Models;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalRequest extends Model
{
    public const TYPE_SUBPOENA = 'subpoena';
    public const TYPE_SEARCH_WARRANT = 'search_warrant';
    public const TYPE_2703F_PRESERVATION = '2703f_preservation';
    public const TYPE_2257_AUDIT = '2257_audit';
    public const TYPE_COURT_ORDER = 'court_order';
    public const TYPE_EMERGENCY_DISCLOSURE = 'emergency_disclosure';

    public const STATUS_RECEIVED = 'received';
    public const STATUS_UNDER_LEGAL_REVIEW = 'under_legal_review';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'reference_code',
        'agency_name',
        'officer_name',
        'badge_number',
        'official_email',
        'court_jurisdiction',
        'request_type',
        'case_number',
        'target_user_id',
        'target_listing_id',
        'description',
        'status',
        'risk_level',
        'is_emergency',
        'handled_by_admin_id',
        'fulfilled_at',
        'compliance_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_emergency' => 'boolean',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function targetListing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'target_listing_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_admin_id');
    }

    public function preservations(): HasMany
    {
        return $this->hasMany(EvidencePreservation::class, 'legal_request_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_RECEIVED, self::STATUS_UNDER_LEGAL_REVIEW]);
    }

    public function scopeEmergency(Builder $query): Builder
    {
        return $query->where('is_emergency', true);
    }
}
