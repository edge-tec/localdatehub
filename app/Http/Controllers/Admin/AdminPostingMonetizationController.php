<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Monetization\Models\AccountPostingLimit;
use App\Modules\Monetization\Models\PostingPricingRule;
use App\Modules\Monetization\Models\PostingSetting;
use App\Modules\Monetization\Models\UserPostingOverride;
use Illuminate\Http\Request;

class AdminPostingMonetizationController extends Controller
{
    /**
     * Posting & Monetization Master Control Dashboard.
     */
    public function index()
    {
        $settings = PostingSetting::instance();
        $accountLimits = AccountPostingLimit::all()->keyBy('account_type');
        $pricingRules = PostingPricingRule::with('category')->orderByDesc('priority')->get();
        $userOverrides = UserPostingOverride::with('user')->latest()->paginate(20);
        $categories = Category::whereNull('parent_id')->with('children')->get();

        return view('admin.monetization.index', [
            'settings' => $settings,
            'accountLimits' => $accountLimits,
            'pricingRules' => $pricingRules,
            'userOverrides' => $userOverrides,
            'categories' => $categories,
        ]);
    }

    /**
     * Update Global Posting Settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'global_posting_mode' => 'required|in:free,paid,quota_then_paid,account_based,category_based',
            'default_post_price_credits' => 'required|integer|min:0',
            'default_free_quota' => 'required|integer|min:0',
            'unlimited_posting_enabled' => 'boolean',
            'daily_limit' => 'nullable|integer|min:1',
            'weekly_limit' => 'nullable|integer|min:1',
            'monthly_limit' => 'nullable|integer|min:1',
            'approval_mode' => 'required|in:auto,manual,conditional',
            'new_account_manual_review' => 'boolean',
            'verified_user_auto_approve' => 'boolean',
            'verified_user_ranking_boost' => 'required|integer|min:0|max:1000',
        ]);

        $settings = PostingSetting::instance();
        $settings->update([
            'global_posting_mode' => $validated['global_posting_mode'],
            'default_post_price_credits' => $validated['default_post_price_credits'],
            'default_free_quota' => $validated['default_free_quota'],
            'unlimited_posting_enabled' => (bool) $request->input('unlimited_posting_enabled', false),
            'daily_limit' => $validated['daily_limit'] ?: null,
            'weekly_limit' => $validated['weekly_limit'] ?: null,
            'monthly_limit' => $validated['monthly_limit'] ?: null,
            'approval_mode' => $validated['approval_mode'],
            'new_account_manual_review' => (bool) $request->input('new_account_manual_review', false),
            'verified_user_auto_approve' => (bool) $request->input('verified_user_auto_approve', false),
            'verified_user_ranking_boost' => $validated['verified_user_ranking_boost'],
        ]);

        return back()->with('status', 'Global posting settings updated successfully.');
    }

    /**
     * Update Account-Type Specific Posting Limits.
     */
    public function updateAccountLimits(Request $request, string $accountType)
    {
        $validated = $request->validate([
            'daily_limit' => 'nullable|integer|min:0',
            'weekly_limit' => 'nullable|integer|min:0',
            'monthly_limit' => 'nullable|integer|min:0',
            'free_post_quota' => 'required|integer|min:0',
            'is_unlimited' => 'boolean',
            'post_price_credits' => 'nullable|integer|min:0',
            'approval_mode' => 'required|in:inherit,auto,manual',
        ]);

        AccountPostingLimit::updateOrCreate(
            ['account_type' => $accountType],
            [
                'daily_limit' => $validated['daily_limit'] ?: null,
                'weekly_limit' => $validated['weekly_limit'] ?: null,
                'monthly_limit' => $validated['monthly_limit'] ?: null,
                'free_post_quota' => $validated['free_post_quota'],
                'is_unlimited' => (bool) $request->input('is_unlimited', false),
                'post_price_credits' => $validated['post_price_credits'] !== null ? (int) $validated['post_price_credits'] : null,
                'approval_mode' => $validated['approval_mode'],
            ]
        );

        return back()->with('status', "Posting limits for [{$accountType}] updated successfully.");
    }

    /**
     * Store Category/Account-specific Pricing Rule.
     */
    public function storePricingRule(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'account_type' => 'nullable|in:new_account,regular,verified,premium,business',
            'price_credits' => 'required|integer|min:0',
            'is_free' => 'boolean',
            'priority' => 'required|integer|min:1|max:100',
        ]);

        PostingPricingRule::create([
            'category_id' => $validated['category_id'] ?: null,
            'account_type' => $validated['account_type'] ?: null,
            'price_credits' => $validated['price_credits'],
            'is_free' => (bool) $request->input('is_free', false),
            'priority' => $validated['priority'],
            'is_active' => true,
        ]);

        return back()->with('status', 'Pricing rule created successfully.');
    }

    /**
     * Delete Pricing Rule.
     */
    public function deletePricingRule(PostingPricingRule $rule)
    {
        $rule->delete();
        return back()->with('status', 'Pricing rule deleted successfully.');
    }

    /**
     * Store or update individual user override.
     */
    public function storeUserOverride(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'custom_quota' => 'nullable|integer|min:0',
            'free_post_allowance' => 'nullable|integer|min:0',
            'is_unlimited' => 'boolean',
            'custom_price_credits' => 'nullable|integer|min:0',
            'approval_mode' => 'required|in:inherit,auto,manual',
            'is_posting_restricted' => 'boolean',
            'restriction_reason' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date',
        ]);

        UserPostingOverride::updateOrCreate(
            ['user_id' => $validated['user_id']],
            [
                'custom_quota' => $validated['custom_quota'] !== null ? (int) $validated['custom_quota'] : null,
                'free_post_allowance' => $validated['free_post_allowance'] !== null ? (int) $validated['free_post_allowance'] : null,
                'is_unlimited' => (bool) $request->input('is_unlimited', false),
                'custom_price_credits' => $validated['custom_price_credits'] !== null ? (int) $validated['custom_price_credits'] : null,
                'approval_mode' => $validated['approval_mode'],
                'is_posting_restricted' => (bool) $request->input('is_posting_restricted', false),
                'restriction_reason' => $validated['restriction_reason'] ?: null,
                'expires_at' => $validated['expires_at'] ?: null,
            ]
        );

        return back()->with('status', 'User posting override saved successfully.');
    }

    /**
     * Delete user override.
     */
    public function deleteUserOverride(UserPostingOverride $override)
    {
        $override->delete();
        return back()->with('status', 'User posting override removed.');
    }
}

