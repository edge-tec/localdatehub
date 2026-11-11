<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingContact;
use App\Modules\Listings\Models\ListingLocation;
use App\Modules\Listings\Models\ListingMedia;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatingListingsProductionSeeder extends Seeder
{
    public const CONTACT_EMAIL = 'carriethengd@gmail.com';

    public function run(): void
    {
        $jsonPath = database_path('seeders/dating_listings_payload.json');
        if (!file_exists($jsonPath)) {
            $this->command->error("dating_listings_payload.json not found!");
            return;
        }

        $items = json_decode(file_get_contents($jsonPath), true);
        if (empty($items)) {
            $this->command->error("No items found in JSON payload.");
            return;
        }

        $category = Category::where('slug', 'women-seeking-men')->first()
            ?? Category::where('id', 2)->first()
            ?? Category::first();

        $count = 0;
        foreach ($items as $item) {
            DB::transaction(function () use ($item, $category, &$count) {
                // Find or create State & City
                $state = State::where('code', $item['state_code'])->first()
                    ?? State::where('name', $item['state_code'])->first();
                if (!$state) {
                    return;
                }

                $city = City::where('state_id', $state->id)
                    ->where('name', $item['city_name'])
                    ->first();
                if (!$city) {
                    $city = City::where('state_id', $state->id)->first();
                }
                if (!$city) {
                    return;
                }

                // Create or find User
                $user = User::firstOrCreate(
                    ['email' => $item['user_email']],
                    [
                        'name' => $item['user_name'],
                        'username' => $item['username'] ?: 'user_' . Str::random(8),
                        'password' => Hash::make(Str::random(32)),
                        'role' => 'user',
                        'account_status' => 'active',
                        'is_age_verified' => true,
                        'email_verified_at' => now(),
                    ]
                );

                // Create or update Listing
                $listing = Listing::updateOrCreate(
                    ['slug' => $item['slug']],
                    [
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'state_id' => $state->id,
                        'city_id' => $city->id,
                        'zip_code_id' => null,
                        'title' => $item['title'],
                        'description' => $item['description'],
                        'poster_age' => $item['poster_age'],
                        'poster_gender' => $item['poster_gender'],
                        'intent' => $item['intent'],
                        'status' => 'active',
                        'is_featured' => $item['is_featured'],
                        'has_photos' => true,
                        'view_count' => $item['view_count'],
                        'published_at' => $item['published_at'],
                        'expires_at' => $item['expires_at'],
                    ]
                );

                $listing->content_hash = $listing->computeContentHash();
                $listing->save();

                // Location
                ListingLocation::updateOrCreate(
                    ['listing_id' => $listing->id],
                    [
                        'neighborhood' => $city->name . ' Metro',
                        'postal_code' => null,
                        'latitude' => $item['latitude'] ?: 37.7749,
                        'longitude' => $item['longitude'] ?: -122.4194,
                        'fuzzed_latitude' => ($item['latitude'] ?: 37.7749) + (rand(-10, 10) / 1000),
                        'fuzzed_longitude' => ($item['longitude'] ?: -122.4194) + (rand(-10, 10) / 1000),
                        'hide_exact_distance' => false,
                    ]
                );

                // Contact (AES-256 encrypted email at rest)
                ListingContact::updateOrCreate(
                    ['listing_id' => $listing->id],
                    [
                        'allow_platform_messages' => true,
                        'allow_email_relay' => true,
                        'show_email' => false,
                        'masked_relay_email' => 'relay-' . Str::random(8) . '@localdatehub.com',
                        'preferred_email_encrypted' => self::CONTACT_EMAIL,
                        'preferred_contact_method' => 'platform_message',
                        'contact_instructions' => 'Looking forward to genuine and respectful messages. Please introduce yourself!',
                    ]
                );

                // Media
                $filePath = 'images/listings/' . $item['image_filename'];
                ListingMedia::updateOrCreate(
                    ['listing_id' => $listing->id, 'is_primary' => true],
                    [
                        'user_id' => $user->id,
                        'file_path' => $filePath,
                        'thumbnail_path' => $filePath,
                        'original_filename' => $item['original_filename'],
                        'mime_type' => $item['image_mime'],
                        'file_size_bytes' => $item['image_size'],
                        'width' => $item['image_width'],
                        'height' => $item['image_height'],
                        'display_order' => 1,
                        'moderation_status' => 'approved',
                    ]
                );

                $count++;
            });
        }

        $this->command->info("Successfully seeded {$count} dating listings into production database.");
    }
}

