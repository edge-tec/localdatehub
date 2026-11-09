<?php

namespace Database\Seeders;

use App\Modules\Monetization\Models\AccountPostingLimit;
use App\Modules\Monetization\Models\CreditPackage;
use App\Modules\Monetization\Models\PostingSetting;
use Illuminate\Database\Seeder;

class PostingMonetizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Initialize Global Posting Settings
        PostingSetting::updateOrCreate(
            ['id' => 1],
            [
                'global_posting_mode' => 'quota_then_paid',
                'default_post_price_credits' => 10,
                'default_free_quota' => 3,
                'unlimited_posting_enabled' => false,
                'daily_limit' => 5,
                'weekly_limit' => 20,
                'monthly_limit' => 50,
                'approval_mode' => 'conditional',
                'new_account_manual_review' => true,
                'verified_user_auto_approve' => true,
                'verified_user_ranking_boost' => 25,
            ]
        );

        // 2. Initialize Account Posting Limits
        $limits = [
            [
                'account_type' => 'new_account',
                'daily_limit' => 3,
                'weekly_limit' => 10,
                'monthly_limit' => 15,
                'free_post_quota' => 3,
                'is_unlimited' => false,
                'post_price_credits' => 10,
                'approval_mode' => 'manual',
            ],
            [
                'account_type' => 'regular',
                'daily_limit' => 10,
                'weekly_limit' => 30,
                'monthly_limit' => 50,
                'free_post_quota' => 5,
                'is_unlimited' => false,
                'post_price_credits' => 10,
                'approval_mode' => 'inherit',
            ],
            [
                'account_type' => 'verified',
                'daily_limit' => 25,
                'weekly_limit' => 100,
                'monthly_limit' => 200,
                'free_post_quota' => 15,
                'is_unlimited' => false,
                'post_price_credits' => 5,
                'approval_mode' => 'auto',
            ],
            [
                'account_type' => 'premium',
                'daily_limit' => 50,
                'weekly_limit' => 250,
                'monthly_limit' => 500,
                'free_post_quota' => 50,
                'is_unlimited' => true,
                'post_price_credits' => 0,
                'approval_mode' => 'auto',
            ],
            [
                'account_type' => 'business',
                'daily_limit' => 100,
                'weekly_limit' => 500,
                'monthly_limit' => 1000,
                'free_post_quota' => 100,
                'is_unlimited' => true,
                'post_price_credits' => 0,
                'approval_mode' => 'auto',
            ],
        ];

        foreach ($limits as $limit) {
            AccountPostingLimit::updateOrCreate(
                ['account_type' => $limit['account_type']],
                $limit
            );
        }

        // 3. Initialize Credit Packages
        $packages = [
            [
                'name' => 'Starter Pack',
                'credits' => 50,
                'bonus_credits' => 0,
                'price_usd' => 4.99,
                'display_order' => 1,
                'is_popular' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Popular Pack',
                'credits' => 150,
                'bonus_credits' => 25,
                'price_usd' => 12.99,
                'display_order' => 2,
                'is_popular' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Pro Power Pack',
                'credits' => 400,
                'bonus_credits' => 100,
                'price_usd' => 29.99,
                'display_order' => 3,
                'is_popular' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Elite Advertiser Pack',
                'credits' => 1000,
                'bonus_credits' => 300,
                'price_usd' => 69.99,
                'display_order' => 4,
                'is_popular' => false,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $pkg) {
            CreditPackage::updateOrCreate(
                ['name' => $pkg['name']],
                $pkg
            );
        }
    }
}

