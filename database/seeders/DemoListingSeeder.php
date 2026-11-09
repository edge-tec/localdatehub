<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingContact;
use App\Modules\Listings\Models\ListingLocation;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoListingSeeder extends Seeder
{
    public function run(): void
    {
        // Create Administrator
        $admin = User::firstOrCreate(
            ['email' => 'admin@localdatehub.com'],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'password' => Hash::make('Miz@n2129'),
                'role' => 'admin',
                'account_status' => 'active',
                'is_age_verified' => true,
                'is_identity_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create Verified Demo Users
        $demoUsersData = [
            [
                'name' => 'Alex Miller',
                'email' => 'alex.m@example.com',
                'username' => 'alex_austin',
                'city' => 'austin',
                'state' => 'TX',
                'gender' => 'Male',
                'age' => 29,
                'bio' => 'Software developer, avid hiker around Barton Creek, and espresso lover. Looking for genuine local connection.',
            ],
            [
                'name' => 'Sarah Jenkins',
                'email' => 'sarah.j@example.com',
                'username' => 'sarah_atx',
                'city' => 'austin',
                'state' => 'TX',
                'gender' => 'Female',
                'age' => 27,
                'bio' => 'Graphic designer, indie music fan, loves weekend paddle boarding on Lady Bird Lake.',
            ],
            [
                'name' => 'Marcus Chen',
                'email' => 'marcus.c@example.com',
                'username' => 'marcus_ny',
                'city' => 'new-york-city',
                'state' => 'NY',
                'gender' => 'Male',
                'age' => 33,
                'bio' => 'Architect working in Manhattan. Passionate about art museums, rooftop jazz, and Italian dining.',
            ],
            [
                'name' => 'Elena Rodriguez',
                'email' => 'elena.r@example.com',
                'username' => 'elena_la',
                'city' => 'los-angeles',
                'state' => 'CA',
                'gender' => 'Female',
                'age' => 30,
                'bio' => 'Film editor living on the Westside. Enjoy coastal runs, film photography, and farmers markets.',
            ],
        ];

        $users = [];
        foreach ($demoUsersData as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'username' => $u['username'],
                    'password' => Hash::make('PasswordSecure123!'),
                    'role' => 'user',
                    'account_status' => 'active',
                    'is_age_verified' => true,
                    'is_identity_verified' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $u['name'],
                    'bio' => $u['bio'],
                    'gender' => $u['gender'],
                    'birthdate' => now()->subYears($u['age'])->format('Y-m-d'),
                    'city' => ucfirst($u['city']),
                    'state_code' => $u['state'],
                ]
            );

            $users[] = [
                'model' => $user,
                'city' => $u['city'],
                'state' => $u['state'],
                'age' => $u['age'],
                'gender' => $u['gender'],
            ];
        }

        // Demo Listings
        $sampleListings = [
            [
                'user_index' => 0,
                'category_slug' => 'men-seeking-women',
                'title' => 'Indie music and coffee dates in South Congress',
                'description' => "Hey there! 29-year-old developer here in Austin. I love exploring the local coffee scene, going to shows at Mohawk, and walking around Town Lake.\n\nLooking for an open-minded, thoughtful woman who appreciates good humor, live music, and spontaneous weekend road trips to the Hill Country. Let's start with a casual coffee!",
                'intent' => 'dating',
                'is_featured' => true,
            ],
            [
                'user_index' => 1,
                'category_slug' => 'women-seeking-men',
                'title' => 'Creative soul looking for genuine connection & weekend adventures',
                'description' => "Hi! I am a 27-year-old designer based in Central Austin. I value kindness, ambition, and good conversation. Looking to meet a guy who enjoys cultural events, outdoor activities, and cooking nice dinners at home.\n\nNon-smoker, active lifestyle. Let's see if we click over a drink or museum afternoon.",
                'intent' => 'dating',
                'is_featured' => true,
            ],
            [
                'user_index' => 0,
                'category_slug' => 'activity-partners',
                'title' => 'Barton Springs & Greenbelt Trail hiking companion',
                'description' => "Looking for a fun, energetic activity partner to hike the greenbelt on Saturday mornings and hit Barton Springs afterwards. All skill levels welcome, just bring positive energy and trail vibes!",
                'intent' => 'activity',
                'is_featured' => false,
            ],
            [
                'user_index' => 2,
                'category_slug' => 'men-seeking-women',
                'title' => 'Manhattan architect seeking thoughtful companion for gallery nights & dining',
                'description' => "33-year-old professional living in NYC. I love exploring architecture, boutique art galleries in Chelsea, and quiet wine bars. Looking for an intelligent, grounded woman who enjoys engaging conversations and exploring the city.",
                'intent' => 'long_term',
                'is_featured' => true,
            ],
            [
                'user_index' => 3,
                'category_slug' => 'women-seeking-men',
                'title' => 'Westside LA — Coastal walks, cinema, and good food',
                'description' => "30-year-old creative living near Santa Monica. Enjoy morning ocean air, indie cinema, and finding great tacos. Looking for an honest, down-to-earth partner for meaningful dating and laughs.",
                'intent' => 'dating',
                'is_featured' => false,
            ],
        ];

        foreach ($sampleListings as $sl) {
            $userMeta = $users[$sl['user_index']];
            $userModel = $userMeta['model'];
            $category = Category::where('slug', $sl['category_slug'])->first();
            $city = City::where('slug', $userMeta['city'])->first();
            $state = State::where('code', $userMeta['state'])->first();
            $zip = ZipCode::where('city_id', $city?->id)->first();

            if (!$category || !$city || !$state) {
                continue;
            }

            $listing = Listing::firstOrCreate(
                ['slug' => Str::slug($sl['title'])],
                [
                    'user_id' => $userModel->id,
                    'category_id' => $category->id,
                    'state_id' => $state->id,
                    'city_id' => $city->id,
                    'zip_code_id' => $zip?->id,
                    'title' => $sl['title'],
                    'description' => $sl['description'],
                    'poster_age' => $userMeta['age'],
                    'poster_gender' => $userMeta['gender'],
                    'intent' => $sl['intent'],
                    'status' => 'active',
                    'is_featured' => $sl['is_featured'],
                    'is_verified_poster' => true,
                    'has_photos' => false,
                    'view_count' => rand(25, 180),
                    'reply_count' => rand(1, 12),
                    'published_at' => now()->subDays(rand(1, 5)),
                    'expires_at' => now()->addDays(25),
                ]
            );

            ListingLocation::firstOrCreate(
                ['listing_id' => $listing->id],
                [
                    'neighborhood' => 'Downtown / Central',
                    'latitude' => $city->latitude ?? 30.2672,
                    'longitude' => $city->longitude ?? -97.7431,
                    'fuzzed_latitude' => ($city->latitude ?? 30.2672) + 0.0035,
                    'fuzzed_longitude' => ($city->longitude ?? -97.7431) - 0.0042,
                    'postal_code' => $zip?->zip_code,
                    'hide_exact_distance' => false,
                ]
            );

            ListingContact::firstOrCreate(
                ['listing_id' => $listing->id],
                [
                    'allow_platform_messages' => true,
                    'allow_email_relay' => true,
                    'masked_relay_email' => 'relay-' . Str::random(8) . '@localdatehub.com',
                    'preferred_contact_method' => 'platform_message',
                    'contact_instructions' => 'Please message me with a short intro about yourself!',
                ]
            );
        }
    }
}
