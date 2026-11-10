<?php

namespace App\Modules\Verification\Services;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Verification\Models\IdentityVerification;
use App\Modules\Verification\Models\VerificationAuditLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IdentityVerificationService
{
    public const DISK = 'local'; // Using local private storage (not public disk)

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Submit an identity verification request with documents.
     *
     * @param User $user
     * @param string $documentType
     * @param UploadedFile $frontFile
     * @param UploadedFile|null $backFile
     * @param UploadedFile $selfieFile
     * @return IdentityVerification
     */
    public function submitVerification(
        User $user,
        string $documentType,
        UploadedFile $frontFile,
        ?UploadedFile $backFile,
        UploadedFile $selfieFile
    ): IdentityVerification {
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

            return $verification;
        });
    }

    /**
     * Admin approves verification request.
     */
    public function approve(IdentityVerification $verification, User $reviewer, ?string $notes = null): IdentityVerification
    {
        return DB::transaction(function () use ($verification, $reviewer, $notes) {
            $verification->status = IdentityVerification::STATUS_VERIFIED;
            $verification->reviewer_id = $reviewer->id;
            $verification->admin_notes = $notes;
            $verification->reviewed_at = now();
            $verification->rejection_reason = null;
            $verification->save();

            // Upgrade User
            $user = $verification->user;
            $user->is_identity_verified = true;
            $user->save();

            // Upgrade user's active listings with verified badge
            Listing::where('user_id', $user->id)->update([
                'is_verified_poster' => true,
            ]);

            VerificationAuditLog::create([
                'verification_id' => $verification->id,
                'actor_id' => $reviewer->id,
                'action' => 'approved',
                'details' => "Verification approved by administrator {$reviewer->name}. Verified badge activated.",
                'created_at' => now(),
            ]);

            return $verification;
        });
    }

    /**
     * Admin rejects verification request.
     */
    public function reject(IdentityVerification $verification, User $reviewer, string $reason): IdentityVerification
    {
        return DB::transaction(function () use ($verification, $reviewer, $reason) {
            $verification->status = IdentityVerification::STATUS_REJECTED;
            $verification->reviewer_id = $reviewer->id;
            $verification->rejection_reason = $reason;
            $verification->reviewed_at = now();
            $verification->save();

            // Revoke user verified status if was verified
            $user = $verification->user;
            $user->is_identity_verified = false;
            $user->save();

            Listing::where('user_id', $user->id)->update([
                'is_verified_poster' => false,
            ]);

            VerificationAuditLog::create([
                'verification_id' => $verification->id,
                'actor_id' => $reviewer->id,
                'action' => 'rejected',
                'details' => "Verification rejected by {$reviewer->name}. Reason: {$reason}",
                'created_at' => now(),
            ]);

            return $verification;
        });
    }

    /**
     * Admin suspends or revokes verified status.
     */
    public function suspend(IdentityVerification $verification, User $reviewer, string $reason): IdentityVerification
    {
        return DB::transaction(function () use ($verification, $reviewer, $reason) {
            $verification->status = IdentityVerification::STATUS_SUSPENDED;
            $verification->rejection_reason = $reason;
            $verification->save();

            $user = $verification->user;
            $user->is_identity_verified = false;
            $user->save();

            Listing::where('user_id', $user->id)->update([
                'is_verified_poster' => false,
            ]);

            VerificationAuditLog::create([
                'verification_id' => $verification->id,
                'actor_id' => $reviewer->id,
                'action' => 'suspended',
                'details' => "Verification suspended by {$reviewer->name}. Reason: {$reason}",
                'created_at' => now(),
            ]);

            return $verification;
        });
    }
}

