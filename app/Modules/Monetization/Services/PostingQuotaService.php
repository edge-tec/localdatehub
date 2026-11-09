<?php

namespace App\Modules\Monetization\Services;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use App\Modules\Monetization\Models\AccountPostingLimit;
use App\Modules\Monetization\Models\PostingSetting;
use App\Modules\Monetization\Models\UserPostingOverride;
use Carbon\Carbon;

class PostingQuotaService
{
    /**
     * Get a comprehensive summary of a user's posting limits and current usage.
     *
     * @param User $user
     * @return array<string, mixed>
     */
    public function getUserQuotaSummary(User $user): array
    {
        $settings = PostingSetting::instance();
        $accountType = $user->getAccountType();
        $accountLimit = AccountPostingLimit::where('account_type', $accountType)->first();
        $override = UserPostingOverride::where('user_id', $user->id)->first();

        // Check if posting is restricted for this individual
        $isRestricted = false;
        $restrictionReason = null;
        if ($override && $override->is_posting_restricted) {
            if (!$override->expires_at || $override->expires_at->isFuture()) {
                $isRestricted = true;
                $restrictionReason = $override->restriction_reason ?: 'Your account posting privileges have been temporarily suspended by an administrator.';
            }
        }

        // Check unlimited status
        $isUnlimited = false;
        if ($settings->unlimited_posting_enabled) {
            $isUnlimited = true;
        } elseif ($override && $override->is_unlimited) {
            $isUnlimited = true;
        } elseif ($accountLimit && $accountLimit->is_unlimited) {
            $isUnlimited = true;
        }

        // Determine free quota
        $freeQuota = $settings->default_free_quota;
        if ($accountLimit && $accountLimit->free_post_quota !== null) {
            $freeQuota = $accountLimit->free_post_quota;
        }
        if ($override && $override->free_post_allowance !== null) {
            $freeQuota = $override->free_post_allowance;
        }

        // Determine rate limits (daily, weekly, monthly)
        $dailyLimit = $override?->custom_quota ?? $accountLimit?->daily_limit ?? $settings->daily_limit;
        $weeklyLimit = $accountLimit?->weekly_limit ?? $settings->weekly_limit;
        $monthlyLimit = $accountLimit?->monthly_limit ?? $settings->monthly_limit;

        // Count posts published in different windows
        $todayPosts = Listing::where('user_id', $user->id)
            ->whereIn('status', [Listing::STATUS_ACTIVE, Listing::STATUS_PENDING_REVIEW])
            ->where('created_at', '>=', Carbon::now()->startOfDay())
            ->count();

        $weekPosts = Listing::where('user_id', $user->id)
            ->whereIn('status', [Listing::STATUS_ACTIVE, Listing::STATUS_PENDING_REVIEW])
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->count();

        $monthPosts = Listing::where('user_id', $user->id)
            ->whereIn('status', [Listing::STATUS_ACTIVE, Listing::STATUS_PENDING_REVIEW])
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        $lifetimePosts = Listing::where('user_id', $user->id)
            ->whereIn('status', [Listing::STATUS_ACTIVE, Listing::STATUS_PENDING_REVIEW])
            ->count();

        // Calculate free posts remaining this month (or lifetime depending on policy)
        $freeRemaining = max(0, $freeQuota - $monthPosts);

        // Determine can_post flag and reason
        $canPost = true;
        $blockedReason = null;

        if ($isRestricted) {
            $canPost = false;
            $blockedReason = $restrictionReason;
        } elseif (!$isUnlimited) {
            if ($dailyLimit !== null && $todayPosts >= $dailyLimit) {
                $canPost = false;
                $blockedReason = "You have reached your daily posting limit of {$dailyLimit} posts. Please try again tomorrow.";
            } elseif ($weeklyLimit !== null && $weekPosts >= $weeklyLimit) {
                $canPost = false;
                $blockedReason = "You have reached your weekly posting limit of {$weeklyLimit} posts.";
            } elseif ($monthlyLimit !== null && $monthPosts >= $monthlyLimit) {
                $canPost = false;
                $blockedReason = "You have reached your monthly posting limit of {$monthlyLimit} posts.";
            }
        }

        return [
            'user_id' => $user->id,
            'account_type' => $accountType,
            'global_mode' => $settings->global_posting_mode,
            'is_restricted' => $isRestricted,
            'is_unlimited' => $isUnlimited,
            'can_post' => $canPost,
            'blocked_reason' => $blockedReason,
            'free_quota' => $freeQuota,
            'free_remaining' => $freeRemaining,
            'today_posts' => $todayPosts,
            'daily_limit' => $dailyLimit,
            'week_posts' => $weekPosts,
            'weekly_limit' => $weeklyLimit,
            'month_posts' => $monthPosts,
            'monthly_limit' => $monthlyLimit,
            'lifetime_posts' => $lifetimePosts,
        ];
    }

    /**
     * Check if user has free quota remaining.
     */
    public function hasFreeQuotaRemaining(User $user): bool
    {
        $summary = $this->getUserQuotaSummary($user);
        return $summary['is_unlimited'] || $summary['free_remaining'] > 0;
    }

    /**
     * Assert user can post, or throw exception.
     */
    public function assertCanPost(User $user): void
    {
        $summary = $this->getUserQuotaSummary($user);
        if (!$summary['can_post']) {
            throw new \RuntimeException($summary['blocked_reason'] ?: 'Posting quota exceeded.');
        }
    }
}

