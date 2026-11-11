<?php

namespace App\Modules\Verification\Services;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Verification\Models\IdentityVerification;
use App\Modules\Verification\Models\VerificationAuditLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class IdentityVerificationService
{
    public const DISK = 'local'; // Using local private storage (not public disk)

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Authorize that the reviewer is an authorized administrator for ID verification decisions.
     *
     * @throws AuthorizationException
     */
    protected function assertAuthorizedReviewer(User $reviewer): void
    {
        if ($reviewer->isSuspended() || !$reviewer->hasPermission('verification.manage')) {
            throw new AuthorizationException('Only authorized administrators may review or alter identity verifications.');
        }
    }

    /**
     * Synchronize a user's stored badge columns and listing flags strictly from verified DB records.
     */
    public function syncUserBadgeState(User $user): bool
    {
        $validVerification = IdentityVerification::query()
            ->where('user_id', $user->id)
            ->validApproved()
            ->latest('id')
            ->first();

        $latest = IdentityVerification::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $isEligible = $validVerification !== null
            && !($latest && ($latest->isRevoked() || $latest->status === IdentityVerification::STATUS_REJECTED) && $latest->id >= $validVerification->id);

        if ($isEligible) {
            $user->forceFill([
                'is_identity_verified' => true,
                'identity_verified_at' => $validVerification->reviewed_at ?? now(),
                'verified_by_verification_id' => $validVerification->id,
            ])->save();

            Listing::withTrashed()->where('user_id', $user->id)->update(['is_verified_poster' => true]);
        } else {
            $user->forceFill([
                'is_identity_verified' => false,
                'identity_verified_at' => null,
                'verified_by_verification_id' => null,
            ])->save();

            Listing::withTrashed()->where('user_id', $user->id)->update(['is_verified_poster' => false]);
        }

        User::clearVerificationCache($user->id);

        return $isEligible;
    }

    /**
     * Audit every existing account and remediate badge flags that lack approved ID verification.
     * Preserves all users, uploaded ID documents, profile photos, and verification history.
     *
     * @return array<string, int>
     */
    public function auditAllAccounts(bool $applyChanges = true): array
    {
        $stats = [
            'users_scanned' => 0,
            'legitimate_verified_preserved' => 0,
            'incorrect_user_flags_cleared' => 0,
            'incorrect_admin_flags_cleared' => 0,
            'incorrect_listing_flags_cleared' => 0,
        ];

        $validUserIds = IdentityVerification::query()
            ->validApproved()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        User::query()->with('latestVerification')->chunkById(100, function ($users) use (&$stats, $validUserIds, $applyChanges): void {
            foreach ($users as $user) {
                $stats['users_scanned']++;

                $hasValidId = in_array((int) $user->id, $validUserIds, true)
                    && !($user->latestVerification && ($user->latestVerification->isRevoked() || $user->latestVerification->status === IdentityVerification::STATUS_REJECTED));

                $rawUserFlag = (bool) $user->getRawOriginal('is_identity_verified');

                if ($hasValidId) {
                    $stats['legitimate_verified_preserved']++;
                    if ($applyChanges) {
                        $this->syncUserBadgeState($user);
                    }
                    continue;
                }

                if ($rawUserFlag) {
                    $stats['incorrect_user_flags_cleared']++;
                    if ($user->isStaff()) {
                        $stats['incorrect_admin_flags_cleared']++;
                    }
                }

                $badListingsCount = Listing::withTrashed()
                    ->where('user_id', $user->id)
                    ->where('is_verified_poster', true)
                    ->count();
                $stats['incorrect_listing_flags_cleared'] += $badListingsCount;

                if ($applyChanges && ($rawUserFlag || $badListingsCount > 0 || $user->getRawOriginal('verified_by_verification_id') !== null)) {
                    $user->forceFill([
                        'is_identity_verified' => false,
                        'identity_verified_at' => null,
                        'verified_by_verification_id' => null,
                    ])->save();

                    if ($badListingsCount > 0) {
                        Listing::withTrashed()->where('user_id', $user->id)->update(['is_verified_poster' => false]);
                    }

                    User::clearVerificationCache($user->id);
                }
            }
        });

        return $stats;
    }

    /**
     * Submit an identity verification request with documents.
     */
    public function submitVerification(
        User $user,
        string $documentType,
        UploadedFile $frontFile,
        ?UploadedFile $backFile,
        UploadedFile $selfieFile
    ): IdentityVerification {
        if (!in_array($documentType, IdentityVerification::ACCEPTED_DOCUMENT_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported identity document type [{$documentType}].");
        }

        return DB::transaction(function () use ($user, $documentType, $frontFile, $backFile, $selfieFile) {
            $folder = "private/verifications/{$user->id}_" . Str::random(8);

            $frontPath = $frontFile->store($folder, self::DISK);
            $backPath = $backFile ? $backFile->store($folder, self::DISK) : null;
            $selfiePath = $selfieFile->store($folder, self::DISK);

            $verification = IdentityVerification::create([
                'user_id' => $user->id,
                'document_type' => $documentType,
                'document_front_path' => $frontPath,
                'document_back_path' => $backPath,
                'selfie_path' => $selfiePath,
                'status' => IdentityVerification::STATUS_PENDING,
                'reviewer_id' => null,
                'reviewed_at' => null,
                'submitted_at' => now(),
            ]);

            VerificationAuditLog::create([
                'verification_id' => $verification->id,
                'actor_id' => $user->id,
                'action' => 'submitted',
                'details' => "User submitted {$documentType} with selfie for identity verification.",
                'created_at' => now(),
            ]);

            try {
                \App\Modules\Notifications\Models\NotificationLog::create([
                    'user_id' => $user->id,
                    'channel' => 'system',
                    'notification_type' => 'id_verification_submitted',
                    'subject' => "🆔 ID Verification Submitted: {$user->name}",
                    'message' => "User submitted " . str_replace('_', ' ', $documentType) . " with photo ID and selfie for review.",
                    'recipient_target' => 'admin',
                    'status' => 'sent',
                    'is_read' => false,
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {}

            User::clearVerificationCache($user->id);

            return $verification;
        });
    }

    /**
     * Mark a verification request as currently under review by an administrator.
     */
    public function markUnderReview(IdentityVerification $verification, User $reviewer): IdentityVerification
    {
        $this->assertAuthorizedReviewer($reviewer);

        return DB::transaction(function () use ($verification, $reviewer) {
            $verification->status = IdentityVerification::STATUS_UNDER_REVIEW;
            $verification->reviewer_id = $reviewer->id;
            $verification->save();

            VerificationAuditLog::create([
                'verification_id' => $verification->id,
                'actor_id' => $reviewer->id,
                'action' => 'under_review',
                'details' => "Verification moved to active review by administrator {$reviewer->name}.",
                'created_at' => now(),
            ]);

            User::clearVerificationCache((int) $verification->user_id);

            return $verification;
        });
    }

    /**
     * Admin approves verification request after inspecting accepted ID documents.
     */
    public function approve(
        IdentityVerification $verification,
        User $reviewer,
        ?string $notes = null,
        ?\DateTimeInterface $expiresAt = null
    ): IdentityVerification {
        $this->assertAuthorizedReviewer($reviewer);

        if (
            !in_array($verification->document_type, IdentityVerification::ACCEPTED_DOCUMENT_TYPES, true)
            || empty($verification->document_front_path)
            || empty($verification->selfie_path)
        ) {
            throw new InvalidArgumentException('Cannot approve verification without an accepted ID document and live selfie.');
        }

        return DB::transaction(function () use ($verification, $reviewer, $notes, $expiresAt) {
            $verification->status = IdentityVerification::STATUS_VERIFIED;
            $verification->reviewer_id = $reviewer->id;
            $verification->admin_notes = $notes;
            $verification->reviewed_at = now();
            $verification->rejection_reason = null;
            if ($expiresAt !== null) {
                $verification->expires_at = $expiresAt;
            } elseif ($verification->expires_at && $verification->expires_at->isPast()) {
                $verification->expires_at = null;
            }
            $verification->save();

            // Upgrade User strictly via authoritative verification sync
            $user = $verification->user;
            if (!$user->is_age_verified) {
                $user->forceFill(['is_age_verified' => true])->save();
            }
            $this->syncUserBadgeState($user);

            VerificationAuditLog::create([
                'verification_id' => $verification->id,
                'actor_id' => $reviewer->id,
                'action' => 'approved',
                'details' => "Verification approved by administrator {$reviewer->name}. Blue Verified badge activated.",
                'created_at' => now(),
            ]);

            try {
                \App\Modules\Notifications\Models\NotificationLog::create([
                    'user_id' => $user->id,
                    'channel' => 'system',
                    'notification_type' => 'id_verification_approved',
                    'subject' => 'Blue Verified Badge Activated',
                    'message' => 'Your identity verification has been approved by an administrator. Your Blue Verified Badge is now active across your account and listings.',
                    'recipient_target' => $user->email,
                    'status' => 'sent',
                    'is_read' => false,
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {}

            return $verification;
        });
    }

    /**
     * Admin rejects verification request.
     */
    public function reject(IdentityVerification $verification, User $reviewer, string $reason): IdentityVerification
    {
        $this->assertAuthorizedReviewer($reviewer);

        return DB::transaction(function () use ($verification, $reviewer, $reason) {
            $verification->status = IdentityVerification::STATUS_REJECTED;
            $verification->reviewer_id = $reviewer->id;
            $verification->rejection_reason = $reason;
            $verification->reviewed_at = now();
            $verification->save();

            $user = $verification->user;
            $this->syncUserBadgeState($user);

            VerificationAuditLog::create([
                'verification_id' => $verification->id,
                'actor_id' => $reviewer->id,
                'action' => 'rejected',
                'details' => "Verification rejected by {$reviewer->name}. Reason: {$reason}",
                'created_at' => now(),
            ]);

            try {
                \App\Modules\Notifications\Models\NotificationLog::create([
                    'user_id' => $user->id,
                    'channel' => 'system',
                    'notification_type' => 'id_verification_rejected',
                    'subject' => 'Identity Verification Requires Attention',
                    'message' => "Your identity verification request was declined. Reason: {$reason}",
                    'recipient_target' => $user->email,
                    'status' => 'sent',
                    'is_read' => false,
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {}

            return $verification;
        });
    }

    /**
     * Admin requests user to submit a clearer or updated document.
     */
    public function requestResubmission(IdentityVerification $verification, User $reviewer, string $reason): IdentityVerification
    {
        $this->assertAuthorizedReviewer($reviewer);

        return DB::transaction(function () use ($verification, $reviewer, $reason) {
            $verification->status = IdentityVerification::STATUS_RESUBMISSION_REQUIRED;
            $verification->reviewer_id = $reviewer->id;
            $verification->rejection_reason = $reason;
            $verification->reviewed_at = now();
            $verification->save();

            $user = $verification->user;
            $this->syncUserBadgeState($user);

            VerificationAuditLog::create([
                'verification_id' => $verification->id,
                'actor_id' => $reviewer->id,
                'action' => 'resubmission_required',
                'details' => "Resubmission requested by {$reviewer->name}. Reason: {$reason}",
                'created_at' => now(),
            ]);

            try {
                \App\Modules\Notifications\Models\NotificationLog::create([
                    'user_id' => $user->id,
                    'channel' => 'system',
                    'notification_type' => 'id_verification_resubmission_required',
                    'subject' => 'Identity Verification Resubmission Required',
                    'message' => "Please re-submit your identity verification documents. Reason: {$reason}",
                    'recipient_target' => $user->email,
                    'status' => 'sent',
                    'is_read' => false,
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {}

            return $verification;
        });
    }

    /**
     * Admin revokes previously approved verification status.
     */
    public function revoke(IdentityVerification $verification, User $reviewer, string $reason): IdentityVerification
    {
        $this->assertAuthorizedReviewer($reviewer);

        return DB::transaction(function () use ($verification, $reviewer, $reason) {
            $verification->status = IdentityVerification::STATUS_REVOKED;
            $verification->reviewer_id = $reviewer->id;
            $verification->rejection_reason = $reason;
            $verification->reviewed_at = now();
            $verification->save();

            $user = $verification->user;
            // Revoke all previously approved verifications for this user so badge cannot persist via stale records
            IdentityVerification::where('user_id', $user->id)
                ->where('id', '!=', $verification->id)
                ->whereIn('status', IdentityVerification::APPROVED_STATUSES)
                ->update(['status' => IdentityVerification::STATUS_REVOKED]);

            $this->syncUserBadgeState($user);

            VerificationAuditLog::create([
                'verification_id' => $verification->id,
                'actor_id' => $reviewer->id,
                'action' => 'revoked',
                'details' => "Verification revoked by administrator {$reviewer->name}. Reason: {$reason}",
                'created_at' => now(),
            ]);

            try {
                \App\Modules\Notifications\Models\NotificationLog::create([
                    'user_id' => $user->id,
                    'channel' => 'system',
                    'notification_type' => 'id_verification_revoked',
                    'subject' => 'Verified Badge Revoked',
                    'message' => "Your Blue Verified Badge has been revoked. Reason: {$reason}",
                    'recipient_target' => $user->email,
                    'status' => 'sent',
                    'is_read' => false,
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {}

            return $verification;
        });
    }

    /**
     * Backward-compatible alias for revoking/suspending verification.
     */
    public function suspend(IdentityVerification $verification, User $reviewer, string $reason): IdentityVerification
    {
        return $this->revoke($verification, $reviewer, $reason);
    }
}

