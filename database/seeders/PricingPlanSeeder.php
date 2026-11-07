<?php

namespace Database\Seeders;

use App\Modules\Billing\Models\PricingPlan;
use Illuminate\Database\Seeder;

class PricingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Standard Free Listing',
                'slug' => 'free-listing',
                'description' => 'Publish your personal classified for 30 days with full contact options.',
                'price_cents' => 0,
                'currency' => 'USD',
                'duration_days' => 30,
                'plan_type' => 'listing_upgrade',
                'features' => [
                    'Active for 30 days',
                    'Up to 4 sanitized photos',
                    'Masked email relay',
                    'Standard city search placement',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'City Featured Highlight',
                'slug' => 'city-featured',
                'description' => 'Pin your listing at the top of your city and category for 7 days.',
                'price_cents' => 499,
                'currency' => 'USD',
                'duration_days' => 7,
                'plan_type' => 'featured_spot',
                'features' => [
                    'Highlighted card border & badge',
                    'Top of city category search',
                    '3x average view count',
                    'Priority moderation review',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Metro Area Spotlight',
                'slug' => 'metro-spotlight',
                'description' => 'Featured prominence across all adjacent cities in your metro area for 14 days.',
                'price_cents' => 999,
                'currency' => 'USD',
                'duration_days' => 14,
                'plan_type' => 'featured_spot',
                'features' => [
                    'Metro-wide visibility',
                    'Homepage spotlight rotation',
                    'Up to 8 high-res photos',
                    'Real-time view analytics',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'LocalDateHub Verified Plus',
                'slug' => 'verified-plus-monthly',
                'description' => 'Monthly membership with verified trust badge and unlimited featured bumps.',
                'price_cents' => 1499,
                'currency' => 'USD',
                'duration_days' => 30,
                'plan_type' => 'membership',
                'features' => [
                    'Verified Adult identity badge',
                    'Unlimited listing renewals',
                    '2 free Featured bumps per month',
                    'Advanced privacy controls',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $p) {
            PricingPlan::firstOrCreate(['slug' => $p['slug']], $p);
        }
    }
}
