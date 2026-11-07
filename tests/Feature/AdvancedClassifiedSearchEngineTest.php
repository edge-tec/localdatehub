<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingLocation;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use App\Modules\Search\DTOs\SearchFilterDTO;
use App\Modules\Search\Drivers\DatabaseSearchDriver;
use App\Modules\Search\Drivers\MeilisearchDriver;
use App\Modules\Search\Jobs\RemoveListingFromSearchIndexJob;
use App\Modules\Search\Jobs\SyncListingToSearchIndexJob;
use App\Modules\Search\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdvancedClassifiedSearchEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_search_by_category_and_subcategory(): void
    {
        $user = User::first();
        $parentCat = Category::where('slug', 'dating')->first() ?? Category::first();
        $subCat = Category::where('parent_id', $parentCat->id)->first() ?? Category::create([
            'name' => 'Sub Category Test',
            'slug' => 'sub-cat-test',
            'parent_id' => $parentCat->id,
            'is_active' => true,
        ]);
        $city = City::first();

        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $subCat->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Subcategory Matching Classified Listing',
            'slug' => 'subcat-matching-' . uniqid(),
            'description' => 'A listing placed in a subcategory.',
            'content_hash' => hash('sha256', 'subcat matching'),
            'poster_age' => 28,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // Search by parent category slug - should match via descendantIds
        $responseParent = $this->get(route('search.index', ['category' => $parentCat->slug]));
        $responseParent->assertStatus(200);
        $responseParent->assertSee('Subcategory Matching Classified Listing');

        // Search by explicit subcategory
        $responseSub = $this->get(route('search.index', ['subcategory' => $subCat->slug]));
        $responseSub->assertStatus(200);
        $responseSub->assertSee('Subcategory Matching Classified Listing');
    }

    public function test_search_by_state_city_and_zip_code(): void
    {
        $user = User::first();
        $category = Category::first();
        $state = State::where('code', 'TX')->first() ?? State::first();
        $city = City::where('state_id', $state->id)->first() ?? City::first();

        $zip = ZipCode::firstOrCreate(
            ['zip_code' => '78701'],
            [
                'city_id' => $city->id,
                'state_id' => $state->id,
                'latitude' => 30.2711,
                'longitude' => -97.7437,
            ]
        );

        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'zip_code_id' => $zip->id,
            'title' => 'Downtown Austin Zip Code Match',
            'slug' => 'downtown-austin-zip-' . uniqid(),
            'description' => 'Listing located in downtown Austin zip 78701.',
            'content_hash' => hash('sha256', 'downtown austin zip'),
            'poster_age' => 32,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        ListingLocation::create([
            'listing_id' => $listing->id,
            'latitude' => 30.2711,
            'longitude' => -97.7437,
            'fuzzed_latitude' => 30.2750,
            'fuzzed_longitude' => -97.7410,
        ]);

        // Search by state
        $resState = $this->get(route('search.index', ['state' => $state->code]));
        $resState->assertStatus(200);
        $resState->assertSee('Downtown Austin Zip Code Match');

        // Search by city
        $resCity = $this->get(route('search.index', ['city' => $city->name]));
        $resCity->assertStatus(200);
        $resCity->assertSee('Downtown Austin Zip Code Match');

        // Search by ZIP code
        $resZip = $this->get(route('search.index', ['zip' => '78701']));
        $resZip->assertStatus(200);
        $resZip->assertSee('Downtown Austin Zip Code Match');
    }

    public function test_search_by_spatial_distance_radius(): void
    {
        $user = User::first();
        $category = Category::first();
        $city = City::first();

        // Listing A in Austin center
        $listingA = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Nearby Austin Center Listing',
            'slug' => 'nearby-austin-' . uniqid(),
            'description' => 'Listing located in central area.',
            'content_hash' => hash('sha256', 'nearby austin'),
            'poster_age' => 30,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        ListingLocation::create([
            'listing_id' => $listingA->id,
            'latitude' => 30.2672,
            'longitude' => -97.7431,
            'fuzzed_latitude' => 30.2672,
            'fuzzed_longitude' => -97.7431,
        ]);

        // Listing B far away (e.g. Seattle ~3000 km away)
        $listingB = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Far Away Listing Distinct Area',
            'slug' => 'far-away-' . uniqid(),
            'description' => 'Listing located far away.',
            'content_hash' => hash('sha256', 'far away listing'),
            'poster_age' => 30,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        ListingLocation::create([
            'listing_id' => $listingB->id,
            'latitude' => 47.6062,
            'longitude' => -122.3321,
            'fuzzed_latitude' => 47.6062,
            'fuzzed_longitude' => -122.3321,
        ]);

        // Search near Austin with 25 km radius
        $response = $this->get(route('search.index', [
            'lat' => 30.2672,
            'lng' => -97.7431,
            'radius' => 25,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Nearby Austin Center Listing');
        $response->assertDontSee('Far Away Listing Distinct Area');
    }

    public function test_search_by_published_date_filter(): void
    {
        $user = User::first();
        $category = Category::first();
        $city = City::first();

        // Listing published today (2 hours ago)
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Published Today Fresh Classified',
            'slug' => 'published-today-' . uniqid(),
            'description' => 'Published 2 hours ago.',
            'content_hash' => hash('sha256', 'today fresh'),
            'poster_age' => 29,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now()->subHours(2),
            'expires_at' => now()->addDays(30),
        ]);

        // Listing published 20 days ago
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Published Weeks Ago Old Classified',
            'slug' => 'published-weeks-ago-' . uniqid(),
            'description' => 'Published 20 days ago.',
            'content_hash' => hash('sha256', 'weeks ago'),
            'poster_age' => 35,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now()->subDays(20),
            'expires_at' => now()->addDays(10),
        ]);

        // Filter published_date=today
        $resToday = $this->get(route('search.index', ['published_date' => 'today']));
        $resToday->assertStatus(200);
        $resToday->assertSee('Published Today Fresh Classified');
        $resToday->assertDontSee('Published Weeks Ago Old Classified');

        // Filter published_date=7d
        $res7d = $this->get(route('search.index', ['published_date' => '7d']));
        $res7d->assertStatus(200);
        $res7d->assertSee('Published Today Fresh Classified');
        $res7d->assertDontSee('Published Weeks Ago Old Classified');
    }

    public function test_featured_status_filter_and_transparent_ranking_order(): void
    {
        $user = User::first();
        $category = Category::first();
        $city = City::first();

        // Organic listing published 1 hour ago
        $organic = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Organic Fresh Listing Non-Featured',
            'slug' => 'organic-fresh-' . uniqid(),
            'description' => 'Organic ad description.',
            'content_hash' => hash('sha256', 'organic fresh'),
            'poster_age' => 30,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'is_featured' => false,
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now()->subHour(),
            'expires_at' => now()->addDays(30),
        ]);

        // Featured listing published 2 days ago
        $featured = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Featured Spotlight Priority Placement',
            'slug' => 'featured-spotlight-' . uniqid(),
            'description' => 'Featured ad description.',
            'content_hash' => hash('sha256', 'featured spotlight'),
            'poster_age' => 31,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'is_featured' => true,
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now()->subDays(2),
            'expires_at' => now()->addDays(30),
        ]);

        // Filter featured_only=1
        $resFeaturedOnly = $this->get(route('search.index', ['featured_only' => 1]));
        $resFeaturedOnly->assertStatus(200);
        $resFeaturedOnly->assertSee('Featured Spotlight Priority Placement');
        $resFeaturedOnly->assertDontSee('Organic Fresh Listing Non-Featured');

        // Transparent ranking test: In default newest sort, featured ad must appear before organic ad
        $driver = app(DatabaseSearchDriver::class);
        $results = $driver->search(SearchFilterDTO::fromArray(['sort' => 'newest']), 10);
        
        $itemTitles = collect($results->items())->pluck('title')->all();
        $featuredIndex = array_search('Featured Spotlight Priority Placement', $itemTitles, true);
        $organicIndex = array_search('Organic Fresh Listing Non-Featured', $itemTitles, true);

        $this->assertNotFalse($featuredIndex);
        $this->assertNotFalse($organicIndex);
        $this->assertLessThan($organicIndex, $featuredIndex, "Featured listing must rank ahead of organic listing according to transparent ranking rules.");
    }

    public function test_strict_exclusion_of_non_active_restricted_listings(): void
    {
        $user = User::first();
        $category = Category::first();
        $city = City::first();

        // Draft
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Hidden Secret Draft Listing',
            'slug' => 'hidden-draft-' . uniqid(),
            'description' => 'Should never appear in search results.',
            'content_hash' => hash('sha256', 'hidden draft'),
            'poster_age' => 30,
            'status' => Listing::STATUS_DRAFT,
        ]);

        // Pending Review
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Pending Moderation Unapproved Listing',
            'slug' => 'pending-mod-' . uniqid(),
            'description' => 'Should never appear in search results.',
            'content_hash' => hash('sha256', 'pending mod'),
            'poster_age' => 30,
            'status' => Listing::STATUS_PENDING_REVIEW,
        ]);

        // Rejected
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Rejected Policy Violating Listing',
            'slug' => 'rejected-ad-' . uniqid(),
            'description' => 'Should never appear in search results.',
            'content_hash' => hash('sha256', 'rejected ad'),
            'poster_age' => 30,
            'status' => Listing::STATUS_REJECTED,
        ]);

        // Expired
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Expired Time Elapsing Listing',
            'slug' => 'expired-ad-' . uniqid(),
            'description' => 'Should never appear in search results.',
            'content_hash' => hash('sha256', 'expired ad'),
            'poster_age' => 30,
            'status' => Listing::STATUS_EXPIRED,
            'expires_at' => now()->subDay(),
        ]);

        // Suspended
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Suspended Terminated Listing',
            'slug' => 'suspended-ad-' . uniqid(),
            'description' => 'Should never appear in search results.',
            'content_hash' => hash('sha256', 'suspended ad'),
            'poster_age' => 30,
            'status' => Listing::STATUS_SUSPENDED,
        ]);

        $response = $this->get(route('search.index'));
        $response->assertStatus(200);

        $response->assertDontSee('Hidden Secret Draft Listing');
        $response->assertDontSee('Pending Moderation Unapproved Listing');
        $response->assertDontSee('Rejected Policy Violating Listing');
        $response->assertDontSee('Expired Time Elapsing Listing');
        $response->assertDontSee('Suspended Terminated Listing');
    }

    public function test_autocomplete_api_returns_suggestions(): void
    {
        $response = $this->getJson(route('api.search.autocomplete', ['q' => 'Aus']));
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'cities',
            'zips',
            'categories',
        ]);

        $this->assertIsArray($response->json('cities'));
    }

    public function test_search_service_falls_back_cleanly_when_meilisearch_unreachable(): void
    {
        config(['services.meilisearch.host' => 'http://127.0.0.1:9999']);
        $meiliDriver = app(MeilisearchDriver::class);
        $this->assertFalse($meiliDriver->isAvailable(), "Driver must report unavailable when host is unreachable.");

        $service = app(SearchService::class);
        $this->assertEquals('database', $service->getActiveDriverName());

        $results = $service->searchListings(SearchFilterDTO::fromArray(['sort' => 'newest']), 10);
        $this->assertNotNull($results);
    }

    public function test_search_jobs_dispatch_and_execute_safely(): void
    {
        $user = User::first();
        $category = Category::first();
        $city = City::first();

        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Job Sync Verification Listing',
            'slug' => 'job-sync-test-' . uniqid(),
            'description' => 'Testing index sync job safely.',
            'content_hash' => hash('sha256', 'job sync'),
            'poster_age' => 25,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $service = app(SearchService::class);

        // Execute sync job
        $syncJob = new SyncListingToSearchIndexJob($listing->id);
        $syncJob->handle($service);
        $this->assertTrue(true);

        // Execute removal job
        $removeJob = new RemoveListingFromSearchIndexJob($listing->id);
        $removeJob->handle($service);
        $this->assertTrue(true);
    }

    public function test_sorting_by_views_and_recently_updated(): void
    {
        $user = User::first();
        $category = Category::first();
        $city = City::first();

        // Listing with high views
        $highViews = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Popular Highly Viewed Classified',
            'slug' => 'popular-views-' . uniqid(),
            'description' => '100 views.',
            'content_hash' => hash('sha256', 'popular views'),
            'poster_age' => 27,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'view_count' => 100,
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now()->subDays(5),
            'expires_at' => now()->addDays(25),
        ]);

        // Listing with low views
        $lowViews = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Quiet Low Viewed Classified',
            'slug' => 'quiet-views-' . uniqid(),
            'description' => '2 views.',
            'content_hash' => hash('sha256', 'quiet views'),
            'poster_age' => 27,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'view_count' => 2,
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now()->subDays(5),
            'expires_at' => now()->addDays(25),
        ]);

        $resViews = $this->get(route('search.index', ['sort' => 'views']));
        $resViews->assertStatus(200);
        $resViews->assertSee('Popular Highly Viewed Classified');

        $driver = app(DatabaseSearchDriver::class);
        $viewsResults = $driver->search(SearchFilterDTO::fromArray(['sort' => 'views']), 10);
        $titles = collect($viewsResults->items())->pluck('title')->all();
        $highIndex = array_search('Popular Highly Viewed Classified', $titles, true);
        $lowIndex = array_search('Quiet Low Viewed Classified', $titles, true);

        $this->assertNotFalse($highIndex);
        $this->assertNotFalse($lowIndex);
        $this->assertLessThan($lowIndex, $highIndex);
    }

    public function test_search_by_dynamic_attribute_intent(): void
    {
        $user = User::first();
        $category = Category::first();
        $city = City::first();

        // Casual intent
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Casual Intent Listing Example',
            'slug' => 'casual-intent-' . uniqid(),
            'description' => 'Looking for casual connection.',
            'content_hash' => hash('sha256', 'casual intent'),
            'poster_age' => 30,
            'poster_gender' => 'Male',
            'intent' => 'casual',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // Activity Partner intent
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Tennis Activity Companion Match',
            'slug' => 'tennis-partner-' . uniqid(),
            'description' => 'Looking for tennis partner.',
            'content_hash' => hash('sha256', 'tennis partner'),
            'poster_age' => 30,
            'poster_gender' => 'Female',
            'intent' => 'activity_partner',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->get(route('search.index', ['attr' => ['intent' => 'activity_partner']]));
        $response->assertStatus(200);
        $response->assertSee('Tennis Activity Companion Match');
        $response->assertDontSee('Casual Intent Listing Example');
    }

    public function test_search_maintains_filter_state_in_urls(): void
    {
        $response = $this->get(route('search.index', [
            'q' => 'coffee',
            'state' => 'TX',
            'city' => 'Austin',
            'radius' => 50,
            'with_photos' => 1,
            'sort' => 'views',
        ]));

        $response->assertStatus(200);
        $response->assertSee('value="coffee"', false);
        $response->assertSee('value="Austin"', false);
        $response->assertSee('Within 50 km');
        $response->assertSee('checked', false);
    }
}
