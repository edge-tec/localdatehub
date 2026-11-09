<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Billing\Models\Subscription;
use App\Modules\SEO\Models\CmsPage;
use Illuminate\Database\Seeder;

class EnterpriseContentAndOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@localdatehub.com')->first() ?? User::first();
        $users = User::where('id', '!=', $admin?->id)->take(10)->get();

        // 1. Production CMS Pages
        $pages = [
            [
                'slug' => 'terms-of-service',
                'title' => 'Terms of Service',
                'content' => '<h2>1. Acceptance of Terms</h2><p>Welcome to LocalDateHub. By accessing or using our platform, you agree to be bound by these Terms of Service. You must be at least 18 years of age to access or publish personal classified advertisements.</p><h2>2. Prohibited Conduct</h2><p>Human trafficking, non-consensual content, harassment, illicit commercial solicitation, and exploitation are strictly prohibited and immediately reported to law enforcement authorities.</p><h2>3. Content Moderation & Discretion</h2><p>LocalDateHub employs AI-assisted automated scanning and human moderation to inspect all posted classifieds before and after publication.</p>',
                'is_published' => true,
                'published_at' => now()->subMonths(3),
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'content' => '<h2>Your Privacy Matters</h2><p>LocalDateHub enforces strict confidentiality controls. We do not sell user personal data or contact details to third parties.</p><h2>Data Minimization & EXIF Scrubbing</h2><p>All photos uploaded to LocalDateHub automatically have EXIF geolocation metadata stripped prior to storage.</p><h2>Location Fuzzing</h2><p>Public listing coordinates are fuzzed by random offsets to prevent geographic triangulation while maintaining localized search utility.</p>',
                'is_published' => true,
                'published_at' => now()->subMonths(3),
            ],
            [
                'slug' => 'safety-guidelines',
                'title' => 'Safety & Anti-Trafficking Guidelines',
                'content' => '<h2>Personal Safety First</h2><p>Always meet in well-lit public venues for first encounters. Inform a trusted friend or family member of your schedule.</p><h2>Zero Tolerance for Human Exploitation</h2><p>We actively comply with federal human trafficking prevention directives. Suspicious activities are escalated directly to appropriate legal agencies with cryptographically preserved audit logs.</p>',
                'is_published' => true,
                'published_at' => now()->subMonths(2),
            ],
            [
                'slug' => 'age-verification-faq',
                'title' => '18+ Age Assurance FAQ',
                'content' => '<h2>Why is Age Verification Required?</h2><p>To guarantee community safety and comply with age-restricted classified laws, all advertisers must be at least 18 years of age.</p><h2>How Does Verification Work?</h2><p>Members can submit government photo ID verification or biometric age estimation. All records are stored with salted hash cryptographic tokens.</p>',
                'is_published' => true,
                'published_at' => now()->subMonths(2),
            ],
            [
                'slug' => 'billing-refund-policy',
                'title' => 'Billing & Crypto Payment Policy',
                'content' => '<h2>Transparent Pricing</h2><p>Listing boosts, VIP spotlights, and bumps are clearly priced in USD and supported cryptocurrency tokens (USDT, USDC, BTC, ETH, BNB, TRX).</p><h2>Instant Blockchain Confirmation</h2><p>Crypto payments are monitored by our self-hosted RPC scanner and credited automatically upon required blockchain block confirmations.</p>',
                'is_published' => true,
                'published_at' => now()->subMonth(),
            ],
        ];

        foreach ($pages as $p) {
            CmsPage::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // 2. Active Advertisement Placements
        $ads = [
            [
                'name' => 'VIP Dating Lounge & Events Sponsorship',
                'slot' => 'homepage_top',
                'advertiser_name' => 'Elite Evening Social Club',
                'image_url' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=900&auto=format&fit=crop&q=80',
                'target_url' => 'https://example.com/vip-lounge',
                'is_active' => true,
                'starts_at' => now()->subDays(10),
                'ends_at' => now()->addDays(20),
                'impressions' => 12450,
                'clicks' => 382,
                'price_cents' => 15000,
                'created_by_admin_id' => $admin?->id,
            ],
            [
                'name' => 'Safe Date Concierge & Screening Services',
                'slot' => 'sidebar',
                'advertiser_name' => 'Guardian Dating Verification',
                'image_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=400&auto=format&fit=crop&q=80',
                'target_url' => 'https://example.com/safe-date',
                'is_active' => true,
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addDays(25),
                'impressions' => 8910,
                'clicks' => 245,
                'price_cents' => 9900,
                'created_by_admin_id' => $admin?->id,
            ],
            [
                'name' => 'Luxury Rooftop Lounge Weekend Mixer',
                'slot' => 'search_results',
                'advertiser_name' => 'Skyline Entertainment NY',
                'image_url' => 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?w=900&auto=format&fit=crop&q=80',
                'target_url' => 'https://example.com/skyline-mixer',
                'is_active' => true,
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(12),
                'impressions' => 5430,
                'clicks' => 178,
                'price_cents' => 12000,
                'created_by_admin_id' => $admin?->id,
            ],
        ];

        foreach ($ads as $ad) {
            AdPlacement::updateOrCreate(['name' => $ad['name']], $ad);
        }

        // 3. Age Assurance Submissions (Pending and Approved)
        if ($users->isNotEmpty()) {
            $user1 = $users->get(0);
            $user2 = $users->get(1);
            $user3 = $users->get(2);

            if ($user1) {
                AgeAssurance::updateOrCreate(
                    ['user_id' => $user1->id],
                    [
                        'method' => 'government_id',
                        'status' => 'pending',
                        'declared_birthdate' => '1998-05-14',
                        'document_hash' => hash('sha256', 'mock_id_doc_' . $user1->id),
                        'third_party_reference' => 'VERIFY-USA-' . strtoupper(substr(md5($user1->id), 0, 8)),
                        'verification_metadata' => ['id_type' => 'drivers_license', 'issuing_state' => 'CA', 'face_match_confidence' => 0.94],
                        'created_at' => now()->subHours(3),
                    ]
                );
            }

            if ($user2) {
                AgeAssurance::updateOrCreate(
                    ['user_id' => $user2->id],
                    [
                        'method' => 'biometric_estimation',
                        'status' => 'approved',
                        'declared_birthdate' => '1994-11-22',
                        'document_hash' => hash('sha256', 'mock_biometric_' . $user2->id),
                        'third_party_reference' => 'BIO-EST-' . strtoupper(substr(md5($user2->id), 0, 8)),
                        'verification_metadata' => ['estimated_age' => 30, 'confidence' => 0.98],
                        'reviewed_by_admin_id' => $admin?->id,
                        'verified_at' => now()->subDays(2),
                        'created_at' => now()->subDays(2),
                    ]
                );
            }

            if ($user3) {
                AgeAssurance::updateOrCreate(
                    ['user_id' => $user3->id],
                    [
                        'method' => 'credit_card',
                        'status' => 'pending',
                        'declared_birthdate' => '2001-08-30',
                        'document_hash' => hash('sha256', 'mock_cc_' . $user3->id),
                        'third_party_reference' => 'CC-AUTH-' . strtoupper(substr(md5($user3->id), 0, 8)),
                        'verification_metadata' => ['processor' => 'authorize_net_avs', 'avs_match' => true],
                        'created_at' => now()->subHours(8),
                    ]
                );
            }
        }

        // 4. Active Subscriptions
        $plans = PricingPlan::where('is_active', true)->get();
        if ($plans->isNotEmpty() && $users->isNotEmpty()) {
            $plan1 = $plans->first();
            $plan2 = $plans->count() > 1 ? $plans->get(1) : $plan1;

            if ($users->get(0) && $plan1) {
                Subscription::updateOrCreate(
                    ['user_id' => $users->get(0)->id, 'pricing_plan_id' => $plan1->id],
                    [
                        'status' => 'active',
                        'gateway' => 'stripe',
                        'gateway_subscription_id' => 'sub_live_' . md5($users->get(0)->id),
                        'starts_at' => now()->subDays(10),
                        'ends_at' => now()->addDays(20),
                        'next_renewal_at' => now()->addDays(20),
                        'auto_renew' => true,
                    ]
                );
            }

            if ($users->get(1) && $plan2) {
                Subscription::updateOrCreate(
                    ['user_id' => $users->get(1)->id, 'pricing_plan_id' => $plan2->id],
                    [
                        'status' => 'active',
                        'gateway' => 'crypto_web3',
                        'gateway_subscription_id' => 'sub_web3_' . md5($users->get(1)->id),
                        'starts_at' => now()->subDays(4),
                        'ends_at' => now()->addDays(26),
                        'next_renewal_at' => now()->addDays(26),
                        'auto_renew' => false,
                    ]
                );
            }
        }
    }
}

