<?php

namespace App\Modules\Monetization\Services;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Monetization\Models\AccountPostingLimit;
use App\Modules\Monetization\Models\PostingPricingRule;
use App\Modules\Monetization\Models\PostingSetting;
use App\Modules\Monetization\Models\UserPostingOverride;

class PostingPricingService
{
    /**
     * Calculate authoritative posting price in credits following the strict priority order:
     * 1. Individual user override
     * 2. Category + Account-type specific rule
     * 3. Category specific rule
     * 4. Account-type default price
     * 5. Global default price
     *
     * @param User $user
     * @param int|null $categoryId
     * @return array{price_credits: int, is_free: bool, rule_applied: string}
     */
    public function calculatePostingPrice(User $user, ?int $categoryId = null): array
    {
        $settings = PostingSetting::instance();

        // Check if posting is globally free
        if ($settings->global_posting_mode === 'free') {
            return [
                'price_credits' => 0,
                'is_free' => true,
                'rule_applied' => 'Global Free Posting Mode',
            ];
        }

        // Priority 1: Individual user override
        $override = UserPostingOverride::where('user_id', $user->id)->first();
        if ($override) {
            if ($override->is_unlimited || $override->free_post_allowance > 0) {
                // Check if user has free post allowance remaining
                $quotaService = app(PostingQuotaService::class);
                if ($quotaService->hasFreeQuotaRemaining($user)) {
                    return [
                        'price_credits' => 0,
                        'is_free' => true,
                        'rule_applied' => 'User Free Post Allowance',
                    ];
                }
            }
            if ($override->custom_price_credits !== null) {
                return [
                    'price_credits' => (int) $override->custom_price_credits,
                    'is_free' => (int) $override->custom_price_credits === 0,
                    'rule_applied' => 'Individual User Custom Price',
                ];
            }
        }

        // Check quota: In 'quota_then_paid' mode, if user has free quota remaining, price is 0
        $quotaService = app(PostingQuotaService::class);
        if ($settings->global_posting_mode === 'quota_then_paid' || $settings->global_posting_mode === 'account_based') {
            if ($quotaService->hasFreeQuotaRemaining($user)) {
                return [
                    'price_credits' => 0,
                    'is_free' => true,
                    'rule_applied' => 'Free Account Quota Allowance',
                ];
            }
        }

        $accountType = $user->getAccountType();

        // Priority 2 & 3: Category-specific pricing rules
        if ($categoryId) {
            // Check category + account-type
            $catAccountRule = PostingPricingRule::where('category_id', $categoryId)
                ->where('account_type', $accountType)
                ->where('is_active', true)
                ->orderByDesc('priority')
                ->first();

            if ($catAccountRule) {
                return [
                    'price_credits' => $catAccountRule->is_free ? 0 : $catAccountRule->price_credits,
                    'is_free' => $catAccountRule->is_free,
                    'rule_applied' => 'Category + Account Type Rule',
                ];
            }

            // Check category generic
            $catRule = PostingPricingRule::where('category_id', $categoryId)
                ->whereNull('account_type')
                ->where('is_active', true)
                ->orderByDesc('priority')
                ->first();

            if ($catRule) {
                return [
                    'price_credits' => $catRule->is_free ? 0 : $catRule->price_credits,
                    'is_free' => $catRule->is_free,
                    'rule_applied' => 'Category Specific Rule',
                ];
            }
        }

        // Priority 4: Account-type default price
        $accountLimit = AccountPostingLimit::where('account_type', $accountType)->first();
        if ($accountLimit && $accountLimit->post_price_credits !== null) {
            return [
                'price_credits' => (int) $accountLimit->post_price_credits,
                'is_free' => (int) $accountLimit->post_price_credits === 0,
                'rule_applied' => 'Account Type Default Price',
            ];
        }

        // Priority 5: Global default price
        return [
            'price_credits' => (int) $settings->default_post_price_credits,
            'is_free' => (int) $settings->default_post_price_credits === 0,
            'rule_applied' => 'Global Default Price',
        ];
    }
}

