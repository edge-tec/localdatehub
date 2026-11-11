<?php

namespace App\Modules\Verification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdentityVerification extends Model
{
    use HasFactory;

    protected $table = 'identity_verifications';

    // Canonical Verification Statuses
    public const STATUS_NOT_SUBMITTED = 'not_submitted';
    public const STATUS_NOT_VERIFIED = 'not_verified'; // legacy alias
    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_VERIFIED = 'verified'; // legacy/existing approved status
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RESUBMISSION_REQUIRED = 'resubmission_required';
    public const STATUS_ADDITIONAL_INFO = 'additional_info_required'; // legacy alias
    public const STATUS_ADDITIONAL_INFO_REQUIRED = 'additional_info_required'; // legacy alias
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_SUSPENDED = 'suspended'; // legacy alias
    public const STATUS_EXPIRED = 'expired';

    public const ACCEPTED_DOCUMENT_TYPES = [
        'drivers_license',
        'passport',
        'national_id',
        'state_id',
    ];

    public const APPROVED_STATUSES = [
        self::STATUS_VERIFIED,
        self::STATUS_APPROVED,
    ];

    public const PENDING_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_UNDER_REVIEW,
    ];

    public const RESUBMISSION_STATUSES = [
        self::STATUS_RESUBMISSION_REQUIRED,
        self::STATUS_ADDITIONAL_INFO,
    ];

    public const REVOKED_STATUSES = [
        self::STATUS_REVOKED,
        self::STATUS_SUSPENDED,
    ];

    protected $fillable = [
        'user_id',
        'document_type',
        'document_front_path',
        'document_back_path',
        'selfie_path',
        'status',
        'rejection_reason',
        'admin_notes',
        'reviewer_id',
        'submitted_at',
        'reviewed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $verification): void {
            if ($verification->user_id) {
                User::clearVerificationCache((int) $verification->user_id);
            }
        });

        static::deleted(function (self $verification): void {
            if ($verification->user_id) {
                User::clearVerificationCache((int) $verification->user_id);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(VerificationAuditLog::class, 'verification_id');
    }

    /**
     * Scope for strictly valid, admin-approved, non-revoked, non-expired ID verifications.
     */
    public function scopeValidApproved(Builder $query): Builder
    {
        return $query
            ->whereIn('status', self::APPROVED_STATUSES)
            ->whereIn('document_type', self::ACCEPTED_DOCUMENT_TYPES)
            ->whereNotNull('document_front_path')
            ->where('document_front_path', '!=', '')
            ->whereNotNull('selfie_path')
            ->where('selfie_path', '!=', '')
            ->whereNotNull('reviewer_id')
            ->whereNotNull('reviewed_at')
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Strictly verify all 6 identity verification conditions.
     */
    public function isVerified(): bool
    {
        if (empty($this->user_id)) {
            return false;
        }

        if (!in_array($this->status, self::APPROVED_STATUSES, true)) {
            return false;
        }

        if (!in_array($this->document_type, self::ACCEPTED_DOCUMENT_TYPES, true)) {
            return false;
        }

        if (empty($this->document_front_path) || empty($this->selfie_path)) {
            return false;
        }

        if (empty($this->reviewer_id) || is_null($this->reviewed_at)) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        return true;
    }

    public function isPending(): bool
    {
        return in_array($this->status, self::PENDING_STATUSES, true);
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function requiresResubmission(): bool
    {
        return in_array($this->status, self::RESUBMISSION_STATUSES, true);
    }

    public function isRevoked(): bool
    {
        return in_array($this->status, self::REVOKED_STATUSES, true);
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    /**
     * Normalize verification status to one of the 8 canonical platform states.
     */
    public function canonicalStatus(): string
    {
        if ($this->isExpired()) {
            return self::STATUS_EXPIRED;
        }

        if ($this->isVerified()) {
            return self::STATUS_APPROVED;
        }

        return match ($this->status) {
            self::STATUS_PENDING => self::STATUS_PENDING,
            self::STATUS_UNDER_REVIEW => self::STATUS_UNDER_REVIEW,
            self::STATUS_REJECTED => self::STATUS_REJECTED,
            self::STATUS_RESUBMISSION_REQUIRED, self::STATUS_ADDITIONAL_INFO => self::STATUS_RESUBMISSION_REQUIRED,
            self::STATUS_REVOKED, self::STATUS_SUSPENDED => self::STATUS_REVOKED,
            self::STATUS_EXPIRED => self::STATUS_EXPIRED,
            default => self::STATUS_NOT_SUBMITTED,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->canonicalStatus()) {
            self::STATUS_APPROVED => 'Approved (ID Verified)',
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_RESUBMISSION_REQUIRED => 'Resubmission Required',
            self::STATUS_REVOKED => 'Revoked',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Not Submitted',
        };
    }
}

