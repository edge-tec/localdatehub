<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LocationSeeder::class,
            CategorySeeder::class,
            RoleAndPermissionSeeder::class,
            PricingPlanSeeder::class,
            SystemSettingSeeder::class,
            TrustAndSafetySeeder::class,
            DemoListingSeeder::class,
            TrafficTelemetrySeeder::class,
            EnterpriseContentAndOperationsSeeder::class,
            ContentReportSeeder::class,
            PostingMonetizationSeeder::class,
            CryptoPaymentSeeder::class,
            InHouseCryptoGatewaySeeder::class,
        ]);
    }
}
