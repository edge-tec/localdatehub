<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use App\Modules\Moderation\Models\ContentReport;
use Illuminate\Database\Seeder;

class ContentReportSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->orWhere('role', 'super_admin')->first() ?? User::first();
        $users = User::where('id', '!=', $admin?->id)->take(10)->get();
        $listings = Listing::take(10)->get();

        if ($listings->isEmpty() || $users->isEmpty()) {
            return;
        }

        // Only seed if empty
        if (ContentReport::count() > 0) {
            return;
        }

        $reports = [
            [
                'reporter_id' => $users->get(0)?->id,
                'reportable_type' => Listing::class,
                'reportable_id' => $listings->get(0)->id,
                'reason_code' => 'underage_suspected',
                'details' => 'User mentions being in high school senior year and graduating in 2027. Immediate 18+ age verification required.',
                'evidence_urls' => ['https://images.unsplash.com/photo-1544005313-94ddf0286df2'],
                'status' => 'open',
                'risk_level' => 'critical',
                'is_urgent_takedown' => true,
                'reporter_ip' => '64.233.160.1',
                'created_at' => now()->subMinutes(12),
            ],
            [
                'reporter_id' => $users->get(1)?->id,
                'reportable_type' => Listing::class,
                'reportable_id' => $listings->get(1)->id,
                'reason_code' => 'commercial_solicitation',
                'details' => 'Advertiser asking for upfront CashApp deposit before meeting. Appears to be commercial solicitation.',
                'evidence_urls' => [],
                'status' => 'open',
                'risk_level' => 'high',
                'is_urgent_takedown' => false,
                'reporter_ip' => '172.56.21.89',
                'created_at' => now()->subHours(2),
            ],
            [
                'reporter_id' => $users->get(2)?->id,
                'reportable_type' => Listing::class,
                'reportable_id' => $listings->get(2)->id,
                'reason_code' => 'financial_fraud_scam',
                'details' => 'Requested wire transfer / gift card payment. Matched known advance-fee fraud indicators.',
                'evidence_urls' => ['https://images.unsplash.com/photo-1573496359142-b8d87734a5a2'],
                'status' => 'investigating',
                'risk_level' => 'high',
                'is_urgent_takedown' => false,
                'resolved_by_user_id' => $admin?->id,
                'resolution_notes' => 'Assigned to moderation team. Checking advertiser bank / crypto history.',
                'reporter_ip' => '198.51.100.42',
                'created_at' => now()->subHours(5),
            ],
            [
                'reporter_id' => null, // Anonymous visitor
                'reportable_type' => User::class,
                'reportable_id' => $users->get(3)?->id ?? 2,
                'reason_code' => 'fake_profile',
                'details' => 'Profile pictures belong to a known fashion influencer from Instagram. Stolen identity.',
                'evidence_urls' => [],
                'status' => 'open',
                'risk_level' => 'medium',
                'is_urgent_takedown' => false,
                'reporter_ip' => '203.0.113.19',
                'created_at' => now()->subHours(8),
            ],
            [
                'reporter_id' => $users->get(4)?->id,
                'reportable_type' => Listing::class,
                'reportable_id' => $listings->get(3)?->id ?? $listings->first()->id,
                'reason_code' => 'harassment',
                'details' => 'Posted my private phone number without consent in the ad description.',
                'evidence_urls' => [],
                'status' => 'resolved',
                'risk_level' => 'high',
                'is_urgent_takedown' => false,
                'resolved_by_user_id' => $admin?->id,
                'resolved_at' => now()->subDay(),
                'resolution_notes' => 'Listing sanitized and phone number redacted immediately. Offender issued compliance warning.',
                'reporter_ip' => '104.28.15.6',
                'created_at' => now()->subDays(2),
            ],
            [
                'reporter_id' => $users->get(5)?->id,
                'reportable_type' => Listing::class,
                'reportable_id' => $listings->get(4)?->id ?? $listings->first()->id,
                'reason_code' => 'spam_duplicate',
                'details' => 'Same user reposted this identical ad 5 times in 10 minutes in the wrong category.',
                'evidence_urls' => [],
                'status' => 'dismissed',
                'risk_level' => 'low',
                'is_urgent_takedown' => false,
                'resolved_by_user_id' => $admin?->id,
                'resolved_at' => now()->subDays(3),
                'resolution_notes' => 'Verified as single posting; user already deleted their previous draft.',
                'reporter_ip' => '173.245.48.12',
                'created_at' => now()->subDays(4),
            ],
        ];

        foreach ($reports as $data) {
            ContentReport::create($data);
        }
    }
}

