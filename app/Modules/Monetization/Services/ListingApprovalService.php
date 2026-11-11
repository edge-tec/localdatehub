<?php

namespace App\Modules\Monetization\Services;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use App\Modules\Monetization\Models\AccountPostingLimit;
use App\Modules\Monetization\Models\PostingSetting;
use App\Modules\Monetization\Models\UserPostingOverride;
use App\Modules\Monetization\Models\WalletTransaction;
use App\Modules\Notifications\Notifications\ListingApprovedNotification;
use App\Modules\Notifications\Notifications\ListingRejectedNotification;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class ListingApprovalService
{
    public function __construct(
        protected UserWalletService $walletService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Determine whether a new or updated listing should be published immediately
     * or placed into the pending manual review queue.
     *
     * @param Listing $listing
     * @param User $user
     * @return string Listing::STATUS_ACTIVE or Listing::STATUS_PENDING_REVIEW
     */
    public function determineInitialStatus(Listing $listing, User $user): string
    {
        $settings = PostingSetting::instance();
        $accountType = $user->getAccountType();

        // 1. Check user individual override
        $override = UserPostingOverride::where('user_id', $user->id)->first();
        if ($override && $override->approval_mode !== 'inherit') {
            if ($override->approval_mode === 'manual') {
                return Listing::STATUS_PENDING_REVIEW;
            }
            if ($override->approval_mode === 'auto') {
                return Listing::STATUS_ACTIVE;
            }
        }

        // 2. Global mode override
        if ($settings->approval_mode === 'manual') {
            return Listing::STATUS_PENDING_REVIEW;
        }

        if ($settings->approval_mode === 'auto') {
            return Listing::STATUS_ACTIVE;
        }

        // 3. Conditional Mode checks:
        // New accounts manual review check
        if ($settings->new_account_manual_review && $accountType === 'new_account') {
            return Listing::STATUS_PENDING_REVIEW;
        }

        // Verified accounts auto-approval check
        if ($settings->verified_user_auto_approve && $accountType === 'verified') {
            return Listing::STATUS_ACTIVE;
        }

        // Account-type limit configuration
        $accountLimit = AccountPostingLimit::where('account_type', $accountType)->first();
        if ($accountLimit && $accountLimit->approval_mode !== 'inherit') {
            if ($accountLimit->approval_mode === 'manual') {
                return Listing::STATUS_PENDING_REVIEW;
            }
            if ($accountLimit->approval_mode === 'auto') {
                return Listing::STATUS_ACTIVE;
            }
        }

        // Default safe fallback in conditional mode: regular accounts auto-approve unless flagged
        return Listing::STATUS_ACTIVE;
    }

    /**
     * Approve listing by moderator or admin.
     */
    public function approve(Listing $listing, User $moderator, ?string $notes = null): Listing
    {
        return DB::transaction(function () use ($listing, $moderator, $notes) {
            $listing->status = Listing::STATUS_ACTIVE;
            $listing->rejection_reason = null;
            $listing->published_at = $listing->published_at ?: now();
            $listing->expires_at = now()->addDays(30);

            // Sync verification status strictly from validated ID verification
            $listing->is_verified_poster = $listing->user ? $listing->user->hasVerifiedIdentity() : false;

            $listing->save();

            $listing->recordStatusChange(
                Listing::STATUS_ACTIVE,
                $notes ?: 'Approved by moderator and published.',
                $moderator->id
            );

            if ($listing->user) {
                $this->notificationService->send(
                    $listing->user,
                    new ListingApprovedNotification($listing),
                    'listing_approved'
                );
            }

            return $listing;
        });
    }

    /**
     * Reject listing with explicit reason and optional automatic credit refund.
     */
    public function reject(Listing $listing, User $moderator, string $reason, bool $refundCredits = true): Listing
    {
        return DB::transaction(function () use ($listing, $moderator, $reason, $refundCredits) {
            $listing->status = Listing::STATUS_REJECTED;
            $listing->rejection_reason = $reason;
            $listing->rejection_count += 1;
            $listing->save();

            $listing->recordStatusChange(
                Listing::STATUS_REJECTED,
                $reason,
                $moderator->id
            );

            // Refund credits if paid posting was debited
            if ($refundCredits && $listing->user) {
                $debitTx = WalletTransaction::where('listing_id', $listing->id)
                    ->where('type', WalletTransaction::TYPE_POST_CREATION_DEBIT)
                    ->where('amount', '<', 0)
                    ->latest()
                    ->first();

                if ($debitTx) {
                    $this->walletService->refund($debitTx, "Listing rejection: {$reason}", $moderator->id);
                }
            }

            if ($listing->user) {
                $this->notificationService->send(
                    $listing->user,
                    new ListingRejectedNotification($listing, $reason),
                    'listing_rejected'
                );
            }

            return $listing;
        });
    }
}

