<?php

namespace Database\Seeders;

use App\Models\PlatformTrafficLog;
use App\Models\User;
use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TrafficTelemetrySeeder extends Seeder
{
    /**
     * Seed initial real-time platform traffic logs across the past 7 days
     */
    public function run(): void
    {
        if (!app()->environment('testing')) {
            return;
        }

        if (PlatformTrafficLog::count() > 50) {
            return;
        }

        $countries = [
            ['code' => 'US', 'name' => 'United States', 'weight' => 50],
            ['code' => 'CA', 'name' => 'Canada', 'weight' => 15],
            ['code' => 'GB', 'name' => 'United Kingdom', 'weight' => 12],
            ['code' => 'AU', 'name' => 'Australia', 'weight' => 8],
            ['code' => 'DE', 'name' => 'Germany', 'weight' => 6],
            ['code' => 'BD', 'name' => 'Bangladesh', 'weight' => 5],
            ['code' => 'FR', 'name' => 'France', 'weight' => 4],
        ];

        $devices = ['Desktop', 'Mobile', 'Tablet'];
        $browsers = ['Chrome', 'Safari', 'Firefox', 'Edge'];
        $osList = ['Windows', 'macOS', 'iOS', 'Android'];

        $listings = Listing::pluck('slug')->toArray();
        $samplePaths = ['/', '/listings', '/about', '/contact', '/login', '/register'];
        foreach ($listings as $slug) {
            $samplePaths[] = "/listings/{$slug}";
        }

        $users = User::pluck('id')->toArray();

        // Seed 180 realistic records spread over the past 7 days
        for ($i = 0; $i < 180; $i++) {
            // Pick country based on rough weight
            $rand = rand(1, 100);
            $country = $countries[0];
            $acc = 0;
            foreach ($countries as $c) {
                $acc += $c['weight'];
                if ($rand <= $acc) {
                    $country = $c;
                    break;
                }
            }

            // Time distributed in past 7 days
            $hoursAgo = rand(0, 7 * 24);
            $createdAt = Carbon::now()->subHours($hoursAgo)->subMinutes(rand(1, 59));

            $ip = rand(11, 199) . '.' . rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254);
            $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
            $hash = hash('sha256', $ip . '|' . $ua . '|salt');

            PlatformTrafficLog::create([
                'ip_address' => $ip,
                'visitor_hash' => $hash,
                'country_code' => $country['code'],
                'country_name' => $country['name'],
                'city_name' => null,
                'state_code' => null,
                'path' => $samplePaths[array_rand($samplePaths)],
                'method' => 'GET',
                'referrer' => rand(0, 1) ? 'https://google.com' : null,
                'user_agent' => $ua,
                'device_type' => $devices[array_rand($devices)],
                'browser' => $browsers[array_rand($browsers)],
                'os' => $osList[array_rand($osList)],
                'user_id' => rand(0, 3) === 0 && !empty($users) ? $users[array_rand($users)] : null,
                'session_id' => 'sess_' . bin2hex(random_bytes(16)),
                'is_bot' => rand(1, 100) > 95,
                'response_time_ms' => rand(12, 120),
                'created_at' => $createdAt,
            ]);
        }
    }
}

