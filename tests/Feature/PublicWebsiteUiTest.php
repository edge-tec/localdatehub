<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Categories\Models\Category;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWebsiteUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_homepage_renders_successfully_with_database_data(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('LocalDateHub');
        $response->assertSee('Privacy-First Adult Dating');
        $response->assertSee('Explore Personal Classifications');
        
        // Assert dynamic categories are present
        $category = Category::where('is_active', true)->whereNull('parent_id')->first();
        if ($category) {
            $response->assertSee($category->name);
        }

        // Assert stats ribbon
        $response->assertSee('100% 18+ Age Verified');
        $response->assertSee('Anti-Triangulation Location Fuzzing');
        $response->assertSee('Automated Photo EXIF Stripping');
    }

    public function test_search_page_renders_with_filter_controls(): void
    {
        $response = $this->get(route('search.index'));

        $response->assertStatus(200);
        $response->assertSee('Search Personals');
        $response->assertSee('Refine Search');
        $response->assertSee('Distance Radius');
        $response->assertSee('With Photos Only');
        $response->assertSee('Verified Posters Only');
    }

    public function test_search_filters_by_keyword(): void
    {
        $user = User::first();
        $category = Category::whereNotNull('parent_id')->first();
        $city = City::first();

        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Unique Espresso Connoisseur In Town',
            'slug' => 'unique-espresso-connoisseur-' . uniqid(),
            'description' => 'Looking to discuss specialty beans and pour overs over weekend brunch.',
            'content_hash' => hash('sha256', 'unique espresso'),
            'poster_age' => 31,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->get(route('search.index', ['q' => 'Espresso']));
        $response->assertStatus(200);
        $response->assertSee('Unique Espresso Connoisseur In Town');

        // Querying for something completely different should not display this ad
        $responseNoMatch = $this->get(route('search.index', ['q' => 'SkateboardTrick99']));
        $responseNoMatch->assertStatus(200);
        $responseNoMatch->assertDontSee('Unique Espresso Connoisseur In Town');
    }

    public function test_search_filters_by_category(): void
    {
        $user = User::first();
        $categoryA = Category::where('slug', 'women-seeking-men')->first() ?? Category::first();
        $city = City::first();

        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $categoryA->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Specific Category Classified Listing',
            'slug' => 'specific-category-listing-' . uniqid(),
            'description' => 'A wonderful listing in category A.',
            'content_hash' => hash('sha256', 'category A listing'),
            'poster_age' => 28,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->get(route('search.index', ['category' => $categoryA->slug]));
        $response->assertStatus(200);
        $response->assertSee('Specific Category Classified Listing');
    }

    public function test_search_filters_by_city_and_state(): void
    {
        $user = User::first();
        $category = Category::first();
        $city = City::where('name', 'Austin')->first() ?? City::first();

        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Austin Tech Professional Seeking Activity Partner',
            'slug' => 'austin-tech-pro-' . uniqid(),
            'description' => 'Downtown Austin weekend biking and tacos.',
            'content_hash' => hash('sha256', 'austin tech pro'),
            'poster_age' => 34,
            'poster_gender' => 'Male',
            'intent' => 'activity_partner',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->get(route('search.index', ['city' => $city->name, 'state' => $city->state->code]));
        $response->assertStatus(200);
        $response->assertSee('Austin Tech Professional Seeking Activity Partner');
    }

    public function test_search_filters_by_photos_only_and_age_range(): void
    {
        $user = User::first();
        $category = Category::first();
        $city = City::first();

        // Listing with photos, age 26
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Younger Listing With Photos',
            'slug' => 'younger-photos-' . uniqid(),
            'description' => 'Listing description here.',
            'content_hash' => hash('sha256', 'younger photos'),
            'poster_age' => 26,
            'has_photos' => true,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // Listing without photos, age 55
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Older Listing Without Photos',
            'slug' => 'older-no-photos-' . uniqid(),
            'description' => 'Listing description here.',
            'content_hash' => hash('sha256', 'older no photos'),
            'poster_age' => 55,
            'has_photos' => false,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // Filter with_photos=1
        $responsePhotos = $this->get(route('search.index', ['with_photos' => 1]));
        $responsePhotos->assertStatus(200);
        $responsePhotos->assertSee('Younger Listing With Photos');
        $responsePhotos->assertDontSee('Older Listing Without Photos');

        // Filter age 50-60
        $responseAge = $this->get(route('search.index', ['min_age' => 50, 'max_age' => 60]));
        $responseAge->assertStatus(200);
        $responseAge->assertSee('Older Listing Without Photos');
        $responseAge->assertDontSee('Younger Listing With Photos');
    }

    public function test_public_static_pages_return_successful_responses(): void
    {
        $this->get(route('pages.about'))->assertStatus(200)->assertSee('Connecting Real Adults Safely');
        $this->get(route('pages.safety'))->assertStatus(200)->assertSee('Trust, Safety');
        $this->get(route('pages.contact'))->assertStatus(200)->assertSee("Need Help? We're Here for You!", false);
        $this->get(route('pages.privacy'))->assertStatus(200)->assertSee('Privacy Policy');
        $this->get(route('pages.terms'))->assertStatus(200)->assertSee('Terms of Service');
        $this->get(route('pages.pricing'))->assertStatus(200)->assertSee('Simple, Transparent Listing Options');
    }

    public function test_pricing_page_displays_active_plans(): void
    {
        $response = $this->get(route('pages.pricing'));
        $response->assertStatus(200);

        $plans = PricingPlan::where('is_active', true)->get();
        foreach ($plans as $plan) {
            $response->assertSee($plan->name);
        }
    }

    public function test_contact_form_submits_and_stores_encrypted_message(): void
    {
        $postData = [
            'name' => 'Jane Contributor',
            'email' => 'jane@example.com',
            'phone' => '+15551234567',
            'inquiry_type' => 'safety_report',
            'subject' => 'Question regarding community guidelines',
            'message' => 'Hello, I have a detailed question regarding safety verification on LocalDateHub.',
        ];

        $response = $this->post(route('pages.contact.submit'), $postData);

        $response->assertRedirect(route('pages.contact'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'sender_name' => 'Jane Contributor',
            'inquiry_type' => 'safety_report',
            'subject' => 'Question regarding community guidelines',
        ]);

        $stored = ContactMessage::where('sender_name', 'Jane Contributor')->first();
        $this->assertNotNull($stored);
        $this->assertEquals('jane@example.com', $stored->sender_email_encrypted);
        $this->assertEquals('Hello, I have a detailed question regarding safety verification on LocalDateHub.', $stored->message_encrypted);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->post(route('pages.contact.submit'), []);
        $response->assertSessionHasErrors(['name', 'email', 'inquiry_type', 'subject', 'message']);
    }

    public function test_404_error_page_renders_for_missing_listing(): void
    {
        $response = $this->get('/listings/completely-non-existent-slug-xyz');
        $response->assertStatus(404);
        $response->assertSee('Listing or Page Not Found');
        $response->assertSee('Error 404');
    }

    public function test_navigation_and_footer_contain_all_critical_links(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // Navbar Links
        $response->assertSee(route('search.index'));
        $response->assertSee(route('categories.index'));
        $response->assertSee(route('locations.index'));
        $response->assertSee(route('pages.pricing'));
        $response->assertSee(route('pages.safety'));

        // Footer Links
        $response->assertSee(route('pages.about'));
        $response->assertSee(route('pages.terms'));
        $response->assertSee(route('pages.privacy'));
        $response->assertSee(route('pages.contact'));
    }
}
