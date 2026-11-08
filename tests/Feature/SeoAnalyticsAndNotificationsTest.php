<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Accounts\Models\UserPreference;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Categories\Models\Category;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\AnalyticsEvent;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingAnalytics;
use App\Modules\Listings\Models\SearchTrend;
use App\Modules\Listings\Services\AnalyticsTrackingService;
use App\Modules\Listings\Services\ListingManagementService;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Notifications\Notifications\ContactMessageReceivedNotification;
use App\Modules\Notifications\Notifications\ListingApprovedNotification;
use App\Modules\Notifications\Notifications\ListingExpiringNotification;
use App\Modules\Notifications\Notifications\ListingRejectedNotification;
use App\Modules\Notifications\Notifications\ListingRenewedNotification;
use App\Modules\Notifications\Notifications\ListingSubmittedNotification;
use App\Modules\Notifications\Notifications\PaymentReceiptNotification;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\SEO\Services\SeoService;
use Database\Seeders\PricingPlanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoAnalyticsAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $moderator;
    protected State $state;
    protected City $city;
    protected Category $category;
    protected Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PricingPlanSeeder::class);

        $country = Country::firstOrCreate(['code' => 'US'], ['name' => 'United States', 'is_active' => true]);
        $this->state = State::create(['country_id' => $country->id, 'name' => 'California', 'code' => 'CA', 'slug' => 'california', 'is_active' => true]);
        $this->city = City::create(['state_id' => $this->state->id, 'name' => 'Los Angeles', 'slug' => 'los-angeles', 'is_active' => true]);
        $this->category = Category::create(['name' => 'Men Seeking Women', 'slug' => 'm4w', 'is_active' => true]);

        $this->user = User::factory()->create([
            'is_age_verified' => true,
            'role' => 'user',
        ]);

        $this->moderator = User::factory()->create([
            'is_age_verified' => true,
            'role' => 'listing_moderator',
        ]);

        $this->listing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
            'title' => 'Adventurous Los Angeles Companion Seeking Thoughtful Dates',
            'slug' => 'adventurous-la-companion-' . Str::random(6),
            'description' => 'Athletic professional seeking genuine, fun dates around Downtown LA.',
            'poster_age' => 31,
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'is_featured' => true,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function test_robots_txt_returns_proper_rules_and_sitemap_directive(): void
    {
        $response = $this->get(route('seo.robots'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->assertSee('User-agent: *');
        $response->assertSee('Disallow: /admin/');
        $response->assertSee('Disallow: /account/');
        $response->assertSee('Disallow: /webhooks/');
        $response->assertSee(route('sitemap.index'));
    }

    public function test_sitemap_index_and_sub_sitemaps_return_valid_xml(): void
    {
        // 1. Sitemap Index
        $indexResp = $this->get(route('sitemap.index'));
        $indexResp->assertStatus(200);
        $indexResp->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $indexResp->assertSee('<sitemapindex', false);
        $indexResp->assertSee(route('sitemaps.listings'));

        // 2. Sub-sitemaps
        $this->get(route('sitemaps.pages'))->assertStatus(200)->assertSee('<urlset', false);
        $this->get(route('sitemaps.categories'))->assertStatus(200)->assertSee($this->category->slug);
        $this->get(route('sitemaps.locations'))->assertStatus(200)->assertSee('/locations/ca');
        $this->get(route('sitemaps.listings'))->assertStatus(200)->assertSee($this->listing->slug);
    }

    public function test_seo_service_generates_dynamic_metadata_and_json_ld_schema_for_listings(): void
    {
        $seoService = app(SeoService::class);
        $meta = $seoService->generateForListing($this->listing);

        $this->assertStringContainsString('Adventurous Los Angeles Companion', $meta->title);
        $this->assertStringContainsString('Los Angeles, CA', $meta->title);
        $this->assertStringContainsString('Downtown LA', $meta->description);
        $this->assertEquals(route('listings.show', $this->listing->slug), $meta->canonicalUrl);
        $this->assertEquals('article', $meta->ogType);
        $this->assertEquals('index, follow', $meta->robots);

        $jsonLd = $meta->toJsonLd();
        $this->assertStringContainsString('ItemPage', $jsonLd);
        $this->assertStringContainsString('BreadcrumbList', $jsonLd);
    }

    public function test_category_page_generates_canonical_and_pagination_seo_rules(): void
    {
        $seoService = app(SeoService::class);

        // Page 1: index, follow
        $metaPage1 = $seoService->generateForCategory($this->category, 1);
        $this->assertEquals('index, follow', $metaPage1->robots);

        // Page 2: noindex, follow (prevent pagination bloat)
        $metaPage2 = $seoService->generateForCategory($this->category, 2);
        $this->assertEquals('noindex, follow', $metaPage2->robots);
        $this->assertStringContainsString('Page 2', $metaPage2->title);
    }

    public function test_state_and_city_location_seo_generates_structured_data(): void
    {
        $seoService = app(SeoService::class);

        $stateMeta = $seoService->generateForLocation($this->state);
        $this->assertStringContainsString('California Adult Personals', $stateMeta->title);

        $cityMeta = $seoService->generateForLocation($this->state, $this->city);
        $this->assertStringContainsString('Los Angeles, CA', $cityMeta->title);
        $this->assertStringContainsString('CollectionPage', $cityMeta->toJsonLd());
    }

    public function test_analytics_tracking_service_records_privacy_conscious_view_with_salted_hash(): void
    {
        $analyticsService = app(AnalyticsTrackingService::class);

        $request = Request::create('/listings/' . $this->listing->slug, 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.195',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        ]);

        $initialViews = $this->listing->view_count;

        $analyticsService->recordListingView($this->listing, $request);

        $this->assertEquals($initialViews + 1, $this->listing->fresh()->view_count);

        // Event logged with non-reversible hash, no raw IP
        $event = AnalyticsEvent::where('listing_id', $this->listing->id)->first();
        $this->assertNotNull($event);
        $this->assertEquals('listing_view', $event->event_type);
        $this->assertNotEquals('203.0.113.195', $event->visitor_hash);
        $this->assertEquals(64, strlen($event->visitor_hash));

        // Daily aggregated ListingAnalytics updated
        $daily = ListingAnalytics::where('listing_id', $this->listing->id)->first();
        $this->assertNotNull($daily);
        $this->assertEquals(1, $daily->views);
        $this->assertEquals(1, $daily->unique_visitors);

        // Second view from same visitor should increment views but NOT unique visitors
        $analyticsService->recordListingView($this->listing, $request);
        $this->assertEquals(2, $daily->fresh()->views);
        $this->assertEquals(1, $daily->fresh()->unique_visitors);
    }

    public function test_analytics_tracking_records_contact_clicks_and_favorite_adds(): void
    {
        $analyticsService = app(AnalyticsTrackingService::class);
        $request = Request::create('/', 'POST');

        $analyticsService->recordInteraction($this->listing, 'contact_click', $request);
        $analyticsService->recordInteraction($this->listing, 'favorite_add', $request);

        $this->assertEquals(1, $this->listing->fresh()->reply_count);
        $this->assertEquals(1, $this->listing->fresh()->favorite_count);

        $daily = ListingAnalytics::where('listing_id', $this->listing->id)->first();
        $this->assertEquals(1, $daily->contact_clicks);
        $this->assertEquals(1, $daily->favorite_adds);
    }

    public function test_search_trend_tracking_records_query_hits_and_zero_result_searches(): void
    {
        $analyticsService = app(AnalyticsTrackingService::class);

        $analyticsService->recordSearchQuery('brunette companion', 15, $this->category->id, $this->state->id);
        $analyticsService->recordSearchQuery('brunette companion', 15, $this->category->id, $this->state->id);

        $trend = SearchTrend::where('query', 'brunette companion')->first();
        $this->assertNotNull($trend);
        $this->assertEquals(2, $trend->hits);
        $this->assertEquals(15, $trend->results_count);

        // Zero-result query
        $analyticsService->recordSearchQuery('nonexistent keyword 12345', 0);
        $zeroTrend = SearchTrend::where('query', 'nonexistent keyword 12345')->first();
        $this->assertNotNull($zeroTrend);
        $this->assertEquals(0, $zeroTrend->results_count);
    }

    public function test_listing_submission_approval_and_rejection_dispatch_queued_notifications(): void
    {
        Notification::fake();
        $listingService = app(ListingManagementService::class);

        $draft = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
            'title' => 'Draft Ad for Testing Notifications',
            'slug' => 'draft-ad-' . Str::random(6),
            'description' => 'Detailed description of adult classified ad.',
            'poster_age' => 29,
            'intent' => 'dating',
            'status' => Listing::STATUS_DRAFT,
        ]);

        // 1. Submit
        $listingService->submitForReview($draft, $this->user, ['attributes' => []]);
        Notification::assertSentTo($this->user, ListingSubmittedNotification::class);

        // 2. Reject
        $listingService->rejectListing($draft, 'Incomplete contact details', $this->moderator);
        Notification::assertSentTo($this->user, ListingRejectedNotification::class);

        // 3. Approve
        $listingService->approveListing($draft, $this->moderator);
        Notification::assertSentTo($this->user, ListingApprovedNotification::class);
    }

    public function test_listing_renewal_and_expiring_notifications_dispatch_correctly(): void
    {
        Notification::fake();
        $listingService = app(ListingManagementService::class);

        $listingService->renewListing($this->listing, $this->user);
        Notification::assertSentTo($this->user, ListingRenewedNotification::class);

        // Expiring command test
        $this->listing->update(['expires_at' => now()->addDays(3)->startOfDay()->addHours(12)]);
        $this->artisan('listings:notify-expiring')->assertExitCode(0);
        Notification::assertSentTo($this->user, ListingExpiringNotification::class);
    }

    public function test_contact_message_received_notification_dispatches_to_listing_owner(): void
    {
        Notification::fake();

        $sender = User::factory()->create(['role' => 'user']);

        $message = ContactMessage::create([
            'listing_id' => $this->listing->id,
            'recipient_id' => $this->listing->user_id,
            'sender_id' => $sender->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Michael',
            'sender_email_encrypted' => 'michael@example.com',
            'subject' => "Inquiry regarding: {$this->listing->title}",
            'message_encrypted' => 'Hello, I saw your ad and would love to meet up for coffee!',
        ]);

        $notificationService = app(NotificationService::class);
        $notificationService->send(
            $this->listing->user,
            new ContactMessageReceivedNotification($message),
            'contact_received'
        );

        Notification::assertSentTo($this->listing->user, ContactMessageReceivedNotification::class);
    }

    public function test_user_notification_preferences_suppress_opted_out_notifications(): void
    {
        Notification::fake();

        // Create user preference opting out of 'listing_expiring'
        UserPreference::updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'notification_settings' => [
                    'listing_expiring' => false,
                    'listing_approved' => true,
                ],
            ]
        );

        $notificationService = app(NotificationService::class);

        // Attempt to send opt-out notification
        $sent = $notificationService->send(
            $this->user,
            new ListingExpiringNotification($this->listing, 3),
            'listing_expiring'
        );

        $this->assertFalse($sent);
        Notification::assertNotSentTo($this->user, ListingExpiringNotification::class);

        // Allowed notification sends successfully
        $sentAllowed = $notificationService->send(
            $this->user,
            new ListingApprovedNotification($this->listing),
            'listing_approved'
        );

        $this->assertTrue($sentAllowed);
        Notification::assertSentTo($this->user, ListingApprovedNotification::class);
    }

    public function test_notifications_log_records_delivery_status_and_timestamps(): void
    {
        $notificationService = app(NotificationService::class);

        $notificationService->send(
            $this->user,
            new ListingApprovedNotification($this->listing),
            'listing_approved',
            'Your listing is live!'
        );

        $log = NotificationLog::where('user_id', $this->user->id)
            ->where('notification_type', 'listing_approved')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('mail', $log->channel);
        $this->assertEquals($this->user->email, $log->recipient_target);
        $this->assertEquals('sent', $log->status);
        $this->assertNotNull($log->sent_at);
    }
}