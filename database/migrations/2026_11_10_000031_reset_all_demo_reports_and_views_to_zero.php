<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        // 1. Reset all seeded fake/inflated view counts and reply counts on listings to 0
        if (Schema::hasTable('listings')) {
            DB::table('listings')->update([
                'view_count' => 0,
                'reply_count' => 0,
                'favorite_count' => 0,
            ]);
        }

        // 2. Clear all historical/seeded traffic telemetry logs so Total Views starts at 0
        if (Schema::hasTable('platform_traffic_logs')) {
            DB::table('platform_traffic_logs')->delete();
        }

        // 3. Clear listing_analytics and analytics_events counters
        if (Schema::hasTable('listing_analytics')) {
            DB::table('listing_analytics')->delete();
        }
        if (Schema::hasTable('analytics_events')) {
            DB::table('analytics_events')->delete();
        }

        // 4. Clear any seeded/demo content reports so Reports starts at 0
        if (Schema::hasTable('content_reports')) {
            DB::table('content_reports')->delete();
        }

        // 5. Reset fake ad impressions and clicks to 0
        if (Schema::hasTable('ad_placements')) {
            DB::table('ad_placements')->update([
                'impressions' => 0,
                'clicks' => 0,
            ]);
        }

        // 6. Remove seeded mock age assurance records (mock_id_doc_*, mock_biometric_*, mock_cc_*)
        if (Schema::hasTable('age_assurances')) {
            DB::table('age_assurances')
                ->where('third_party_reference', 'like', 'VERIFY-USA-%')
                ->orWhere('third_party_reference', 'like', 'BIO-EST-%')
                ->orWhere('third_party_reference', 'like', 'CC-AUTH-%')
                ->delete();
        }

        // 7. Remove seeded mock subscriptions (sub_live_*, sub_web3_*)
        if (Schema::hasTable('subscriptions')) {
            DB::table('subscriptions')
                ->where('gateway_subscription_id', 'like', 'sub_live_%')
                ->orWhere('gateway_subscription_id', 'like', 'sub_web3_%')
                ->delete();
        }
    }

    public function down(): void
    {
        // Irreversible cleanup of demo telemetry and reports
    }
};
