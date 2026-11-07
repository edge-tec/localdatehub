<?php

namespace Database\Seeders;

use App\Modules\Admin\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'LocalDateHub', 'group' => 'general', 'type' => 'string', 'description' => 'Platform name', 'is_public' => true],
            ['key' => 'tagline', 'value' => 'Safe & Private Local Adult Personal Classifieds', 'group' => 'general', 'type' => 'string', 'description' => 'Site tagline', 'is_public' => true],
            ['key' => 'min_age_requirement', 'value' => '18', 'group' => 'safety', 'type' => 'integer', 'description' => 'Minimum legal age for platform membership', 'is_public' => true],
            ['key' => 'anti_trafficking_enforcement', 'value' => 'true', 'group' => 'safety', 'type' => 'boolean', 'description' => 'Automated anti-trafficking keyword scanning', 'is_public' => false],
            ['key' => 'location_fuzzing_enabled', 'value' => 'true', 'group' => 'safety', 'type' => 'boolean', 'description' => 'Anti-triangulation random location offset', 'is_public' => false],
            ['key' => 'photo_exif_strip_enabled', 'value' => 'true', 'group' => 'safety', 'type' => 'boolean', 'description' => 'Strip camera metadata and GPS tags from photos', 'is_public' => false],
            ['key' => 'max_photos_per_listing', 'value' => '6', 'group' => 'general', 'type' => 'integer', 'description' => 'Maximum allowed photos per personal ad', 'is_public' => true],
            ['key' => 'default_search_radius_miles', 'value' => '25', 'group' => 'locations', 'type' => 'integer', 'description' => 'Default radius for nearby search', 'is_public' => true],
            ['key' => 'free_listings_enabled', 'value' => 'true', 'group' => 'monetization', 'type' => 'boolean', 'description' => 'Allow free standard personal classifieds', 'is_public' => true],
        ];

        foreach ($settings as $s) {
            SystemSetting::firstOrCreate(['key' => $s['key']], $s);
        }
    }
}
