<?php

namespace App\Modules\ContentGeneration\Services;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingContact;
use App\Modules\Listings\Models\ListingLocation;
use App\Modules\Listings\Models\ListingMedia;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\Models\SeoIndexingQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatingListingGeneratorService
{
    public const CONTACT_EMAIL = 'carriethengd@gmail.com';

    public function __construct(
        protected ImageCatalogService $imageCatalog
    ) {}

    /**
     * Generate dating listings across verified cities and states.
     *
     * @param array $options [
     *     'per_city' => 20,
     *     'state_code' => null, // e.g. 'TX' or null for all states
     *     'max_listings' => null, // total cap or null to use available images
     *     'folders' => null,
     * ]
     * @return array{created: int, cities_covered: int, images_used: int, paused_reason: ?string, listings: array}
     */
    public function generate(array $options = []): array
    {
        $perCity = $options['per_city'] ?? 20;
        $stateCode = $options['state_code'] ?? null;
        $maxListings = $options['max_listings'] ?? null;
        $folders = $options['folders'] ?? null;

        // 1. Fetch Category: Women Seeking Men
        $category = Category::where('slug', 'women-seeking-men')->first()
            ?? Category::where('id', 2)->first()
            ?? Category::first();

        // 2. Fetch available unique images
        $images = $this->imageCatalog->getAvailableImages($folders);
        if (empty($images)) {
            return [
                'created' => 0,
                'cities_covered' => 0,
                'images_used' => 0,
                'paused_reason' => 'No eligible unused images found in source directories. Publishing paused to avoid duplicate image reuse.',
                'listings' => [],
            ];
        }

        // Shuffle images for natural randomized distribution
        shuffle($images);

        // 3. Select target cities
        $cityQuery = City::with('state')
            ->where('is_active', true);

        if ($stateCode) {
            $cityQuery->whereHas('state', function ($q) use ($stateCode) {
                $q->where('code', strtoupper($stateCode))
                  ->orWhere('name', $stateCode);
            });
        }

        // Prioritize popular / major cities, then other verified cities
        $cities = $cityQuery->orderByDesc('is_popular')
            ->orderBy('name')
            ->get();

        if ($cities->isEmpty()) {
            return [
                'created' => 0,
                'cities_covered' => 0,
                'images_used' => 0,
                'paused_reason' => 'No active verified cities found matching criteria.',
                'listings' => [],
            ];
        }

        $createdCount = 0;
        $citiesCovered = 0;
        $createdListings = [];
        $imageIndex = 0;
        $totalImages = count($images);

        foreach ($cities as $city) {
            if ($imageIndex >= $totalImages) {
                break; // Images exhausted, pause cleanly
            }

            if ($maxListings && $createdCount >= $maxListings) {
                break;
            }

            $state = $city->state;
            if (!$state) {
                continue;
            }

            $cityListingCount = 0;

            for ($i = 0; $i < $perCity; $i++) {
                if ($imageIndex >= $totalImages) {
                    break 2; // All available images used; pause to prevent reuse
                }

                if ($maxListings && $createdCount >= $maxListings) {
                    break 2;
                }

                $imageInfo = $images[$imageIndex];
                $imageIndex++;

                // Generate identity, age, and content
                $identity = AmericanNameGenerator::generateUniqueName();
                $age = rand(21, 36);
                $content = DatingContentComposer::compose(
                    $identity['first_name'],
                    $age,
                    $city->name,
                    $state->name,
                    $state->code ?? $state->abbreviation ?? 'USA'
                );

                $listing = DB::transaction(function () use ($identity, $age, $content, $city, $state, $category, $imageInfo) {
                    // Create authentic user profile
                    $uniqueUserEmail = strtolower($identity['first_name'] . '.' . $identity['last_name'] . '.' . Str::random(5) . '@user.localdatehub.com');
                    $user = User::create([
                        'name' => $identity['full_name'],
                        'username' => $identity['username'] . '_' . rand(10, 99),
                        'email' => $uniqueUserEmail,
                        'password' => Hash::make(Str::random(32)),
                        'role' => 'user',
                        'account_status' => 'active',
                        'is_age_verified' => true,
                        'is_identity_verified' => true,
                        'email_verified_at' => now(),
                    ]);

                    // Generate clean SEO-friendly slug
                    $baseSlug = Str::slug($content['title']);
                    $uniqueSlug = $baseSlug . '-' . strtolower($city->slug ?: Str::slug($city->name)) . '-' . Str::random(6);

                    // Create Listing
                    $listing = Listing::create([
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'state_id' => $state->id,
                        'city_id' => $city->id,
                        'zip_code_id' => null,
                        'title' => $content['title'],
                        'slug' => $uniqueSlug,
                        'description' => $content['description'],
                        'poster_age' => $age,
                        'poster_gender' => 'Female',
                        'intent' => 'dating',
                        'status' => Listing::STATUS_ACTIVE,
                        'is_featured' => (rand(1, 6) === 1),
                        'is_verified_poster' => true,
                        'has_photos' => true,
                        'view_count' => rand(1, 15),
                        'published_at' => now()->subMinutes(rand(5, 720)),
                        'expires_at' => now()->addDays(30),
                    ]);

                    $listing->content_hash = $listing->computeContentHash();
                    $listing->save();

                    // Save Location
                    ListingLocation::create([
                        'listing_id' => $listing->id,
                        'neighborhood' => $city->name . ' Metro',
                        'postal_code' => null,
                        'latitude' => $city->latitude ?: 37.7749,
                        'longitude' => $city->longitude ?: -122.4194,
                        'fuzzed_latitude' => ($city->latitude ?: 37.7749) + (rand(-10, 10) / 1000),
                        'fuzzed_longitude' => ($city->longitude ?: -122.4194) + (rand(-10, 10) / 1000),
                        'hide_exact_distance' => false,
                    ]);

                    // Save Encrypted Contact
                    ListingContact::create([
                        'listing_id' => $listing->id,
                        'allow_platform_messages' => true,
                        'allow_email_relay' => true,
                        'show_email' => false, // NEVER expose plaintext email to public/crawlers
                        'masked_relay_email' => 'relay-' . Str::random(8) . '@localdatehub.com',
                        'preferred_email_encrypted' => self::CONTACT_EMAIL, // Encrypted at rest via Laravel encryption
                        'preferred_contact_method' => 'platform_message',
                        'contact_instructions' => 'Looking forward to genuine and respectful messages. Please introduce yourself!',
                    ]);

                    // Copy and record unique image
                    $storedPath = $this->imageCatalog->copyToStorage($imageInfo);

                    ListingMedia::create([
                        'listing_id' => $listing->id,
                        'user_id' => $user->id,
                        'file_path' => $storedPath,
                        'thumbnail_path' => $storedPath,
                        'original_filename' => $imageInfo['filename'],
                        'mime_type' => $imageInfo['mime'],
                        'file_size_bytes' => $imageInfo['size'],
                        'width' => $imageInfo['width'],
                        'height' => $imageInfo['height'],
                        'is_primary' => true,
                        'display_order' => 1,
                        'moderation_status' => 'approved',
                    ]);

                    // Register in SEO Indexing Queue
                    try {
                        SeoIndexingQueue::updateOrCreate(
                            ['url' => url('/listings/' . $listing->slug)],
                            [
                                'action_type' => 'URL_UPDATED',
                                'status' => 'pending',
                                'priority' => 10,
                                'payload' => [
                                    'title' => $listing->title,
                                    'city' => $city->name,
                                    'state' => $state->name,
                                    'category' => $category->name,
                                ]
                            ]
                        );
                    } catch (\Throwable $e) {
                        // Keep transaction intact if indexing queue table is optional
                    }

                    return $listing;
                });

                $createdCount++;
                $cityListingCount++;
                $createdListings[] = [
                    'id' => $listing->id,
                    'title' => $listing->title,
                    'city' => $city->name,
                    'state' => $state->name,
                    'slug' => $listing->slug,
                    'image' => $imageInfo['filename'],
                ];
            }

            if ($cityListingCount > 0) {
                $citiesCovered++;
            }
        }

        $pausedReason = ($imageIndex >= $totalImages)
            ? "Reached image inventory limit ({$totalImages} unique photos). Image-based publishing safely paused to prevent cross-listing image reuse."
            : null;

        return [
            'created' => $createdCount,
            'cities_covered' => $citiesCovered,
            'images_used' => $imageIndex,
            'paused_reason' => $pausedReason,
            'listings' => $createdListings,
        ];
    }
}

