<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Models\AdClick;
use App\Modules\Advertising\Models\AdConversion;
use App\Modules\Advertising\Models\AdDailyStatistic;
use App\Modules\Advertising\Models\AdPlacementConfig;
use App\Modules\Advertising\Services\AdAnalyticsService;
use App\Modules\Advertising\Services\AdCampaignManager;
use App\Modules\Advertising\Services\AdDeliveryService;
use App\Modules\Advertising\Services\AdUrlService;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class EnterpriseAdvertisingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Category $category;
    protected Category $otherCategory;
    protected City $city;
    protected State $state;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::factory()->create([
            'name' => 'Ad SuperAdmin',
            'email' => 'adadmin@example.com',
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $this->regularUser = User::factory()->create([
            'name' => 'Standard Member',
            'email' => 'member@example.com',
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $categories = Category::where('is_active', true)->take(2)->get();
        $this->category = $categories->first();
        $this->otherCategory = $categories->last();

        $this->city = City::with('state')->first();
        $this->state = $this->city->state;
    }

    public function test_admin_can_create_edit_duplicate_pause_and_delete_banner_campaign_with_image_upload(): void
    {
        Storage::fake('public');

        $desktopFile = UploadedFile::fake()->image('hero-banner.jpg', 970, 250);
        $mobileFile = UploadedFile::fake()->image('mobile-banner.png', 320, 100);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'ads'])
            ->call('openCampaignModal')
            ->assertSet('showCampaignModal', true)
            ->set('campaignForm.name', 'Luxury Escort Concierge Banner')
            ->set('campaignForm.campaign_type', 'banner')
            ->set('campaignForm.advertiser_name', 'Concierge Media Ltd')
            ->set('campaignForm.creative_title', 'Meet Verified VIP Companions Tonight')
            ->set('campaignForm.creative_description', 'Discrete local introductions with 100% profile verification.')
            ->set('campaignForm.cta_text', 'Explore VIP')
            ->set('campaignForm.destination_url', 'https://concierge.example.com/vip?utm_source=ldh')
            ->set('campaignForm.alt_text', 'VIP Dating Concierge Banner')
            ->set('campaignForm.disclosure_label', 'Sponsored')
            ->set('campaignForm.status', AdCampaign::STATUS_ACTIVE)
            ->set('campaignForm.priority', 50)
            ->set('campaignForm.total_budget_dollars', '500.00')
            ->set('campaignForm.daily_budget_dollars', '50.00')
            ->set('campaignForm.frequency_cap_per_session', '3')
            ->set('campaignForm.placements', [AdCampaign::SLOT_HOMEPAGE_TOP, AdCampaign::SLOT_BETWEEN_LISTINGS])
            ->set('campaignForm.target_states', [$this->state->code])
            ->set('campaignForm.target_categories', [(string) $this->category->id])
            ->set('campaignDesktopBannerUpload', $desktopFile)
            ->set('campaignMobileBannerUpload', $mobileFile)
            ->call('saveCampaign')
            ->assertHasNoErrors();

        $campaign = AdCampaign::where('name', 'Luxury Escort Concierge Banner')->with(['creatives', 'placements', 'targetingRules'])->first();
        $this->assertNotNull($campaign);
        $this->assertSame(AdCampaign::STATUS_ACTIVE, $campaign->status);
        $this->assertSame(50000, $campaign->total_budget_cents);
        $this->assertSame(5000, $campaign->daily_budget_cents);
        $this->assertSame(3, $campaign->frequency_cap_per_session);
        $this->assertCount(2, $campaign->placements);

        $creative = $campaign->creatives->first();
        $this->assertStringStartsWith('/storage/ad-banners/', $creative->desktop_banner_url);
        $this->assertStringStartsWith('/storage/ad-banners/', $creative->mobile_banner_url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $creative->desktop_banner_url));

        // Duplicate campaign
        $component->call('duplicateCampaign', $campaign->id);
        $clone = AdCampaign::where('name', 'Luxury Escort Concierge Banner (Copy)')->first();
        $this->assertNotNull($clone);
        $this->assertSame(AdCampaign::STATUS_DRAFT, $clone->status);

        // Pause campaign
        $component->call('updateCampaignStatus', $campaign->id, AdCampaign::STATUS_PAUSED);
        $this->assertSame(AdCampaign::STATUS_PAUSED, $campaign->fresh()->status);

        // Soft delete campaign
        $component->call('confirmDeleteCampaign', $campaign->id)
            ->assertSet('showDeleteCampaignModal', true)
            ->call('deleteCampaign');
        $this->assertSoftDeleted('ad_campaigns', ['id' => $campaign->id]);
    }

    public function test_affiliate_tracking_url_with_complex_params_is_preserved_intact_on_save_and_click_redirect(): void
    {
        $rawAffiliateUrl = 'https://partner-network.example.com/go/offer?a=12345&o=9988&aff_sub={macro}&redir=https%3A%2F%2Fdest.example.com%2F%3Fk%3Dv#promo-top';

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'ads'])
            ->call('openCampaignModal')
            ->set('campaignForm.name', 'Exclusive VIP Dating Trial Affiliate')
            ->set('campaignForm.campaign_type', 'affiliate')
            ->set('campaignForm.affiliate_brand_name', 'NightPass VIP')
            ->set('campaignForm.creative_title', 'Unlock 7-Day Free Trial')
            ->set('campaignForm.creative_description', 'Limited partner promotion for verified adult members.')
            ->set('campaignForm.affiliate_tracking_url', $rawAffiliateUrl)
            ->set('campaignForm.cta_text', 'Claim 50% OFF')
            ->set('campaignForm.offer_badge', '50% OFF')
            ->set('campaignForm.disclosure_label', 'Affiliate Offer')
            ->set('campaignForm.click_id_param', 'click_id')
            ->set('campaignForm.subid_param', 'subid')
            ->set('campaignForm.placements', [AdCampaign::SLOT_SEARCH_RESULTS])
            ->set('campaignForm.status', AdCampaign::STATUS_ACTIVE)
            ->call('saveCampaign')
            ->assertHasNoErrors();

        $campaign = AdCampaign::where('name', 'Exclusive VIP Dating Trial Affiliate')->with('creatives')->first();
        $this->assertNotNull($campaign);
        $creative = $campaign->creatives->first();
        $this->assertSame($rawAffiliateUrl, $creative->affiliate_tracking_url);

        // Verify outbound click preserves every original encoded query param and fragment verbatim while appending click_id/subid
        $response = $this->get(route('ads.click', [
            'campaign' => $campaign->uuid,
            'slot' => AdCampaign::SLOT_SEARCH_RESULTS,
            'creative' => $creative->id,
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://partner-network.example.com/go/offer?a=12345&o=9988&aff_sub={macro}&redir=https%3A%2F%2Fdest.example.com%2F%3Fk%3Dv', $location);
        $this->assertStringContainsString('&click_id=clk_', $location);
        $this->assertStringContainsString('&subid=ldh_' . $campaign->id . '_search_results', $location);
        $this->assertStringEndsWith('#promo-top', $location);
        $this->assertSame(1, $campaign->fresh()->total_clicks);
    }

    public function test_unsafe_javascript_urls_and_unauthorized_users_are_blocked(): void
    {
        $this->assertFalse(AdUrlService::isSafeDestinationUrl('javascript:alert(1)'));
        $this->assertFalse(AdUrlService::isSafeDestinationUrl('data:text/html,<script>alert(1)</script>'));
        $this->assertTrue(AdUrlService::isSafeDestinationUrl('https://valid.example.com/deal?x=1'));

        // Unsafe URL rejected during validation
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'ads'])
            ->call('openCampaignModal')
            ->set('campaignForm.name', 'XSS Attempt Campaign')
            ->set('campaignForm.campaign_type', 'affiliate')
            ->set('campaignForm.affiliate_tracking_url', 'javascript:alert(document.cookie)')
            ->call('saveCampaign')
            ->assertHasErrors(['campaignForm.affiliate_tracking_url']);

        // Standard non-staff user cannot access AdminEnterpriseHub
        $this->actingAs($this->regularUser)
            ->get(route('admin.dashboard', ['tab' => 'ads']))
            ->assertStatus(403);
    }

    public function test_geo_category_device_targeting_and_expired_or_paused_filtering(): void
    {
        $manager = app(AdCampaignManager::class);
        $delivery = app(AdDeliveryService::class);

        // Campaign targeted specifically to this state + category + mobile
        $targeted = $manager->saveCampaign([
            'name' => 'Local State Category Mobile Sponsor',
            'campaign_type' => 'banner',
            'advertiser_name' => 'MobileLocal',
            'destination_url' => 'https://mobile.example.com',
            'desktop_banner_url' => 'https://cdn.example.com/banner.jpg',
            'status' => AdCampaign::STATUS_ACTIVE,
            'priority' => 90,
            'placements' => [AdCampaign::SLOT_CATEGORY_PAGE],
            'target_states' => [$this->state->code],
            'target_categories' => [(string) $this->category->id],
            'target_devices' => ['mobile'],
        ], null, $this->admin->id);

        // Expired campaign should never serve
        $manager->saveCampaign([
            'name' => 'Expired Sponsor',
            'campaign_type' => 'banner',
            'destination_url' => 'https://expired.example.com',
            'status' => AdCampaign::STATUS_ACTIVE,
            'starts_at' => now()->subDays(10)->toDateTimeString(),
            'ends_at' => now()->subMinute()->toDateTimeString(),
            'placements' => [AdCampaign::SLOT_CATEGORY_PAGE],
        ], null, $this->admin->id);

        // Matching context returns targeted campaign
        $matched = $delivery->getAdsForSlot(AdCampaign::SLOT_CATEGORY_PAGE, [
            'state' => $this->state->code,
            'category_id' => $this->category->id,
            'device' => 'mobile',
        ], 5, false);

        $this->assertCount(1, $matched);
        $this->assertSame($targeted->id, $matched->first()['campaign_id']);

        // Mismatched device (desktop) returns empty
        $mismatchDevice = $delivery->getAdsForSlot(AdCampaign::SLOT_CATEGORY_PAGE, [
            'state' => $this->state->code,
            'category_id' => $this->category->id,
            'device' => 'desktop',
        ], 5, false);
        $this->assertCount(0, $mismatchDevice);

        // Mismatched category returns empty
        if ($this->otherCategory->id !== $this->category->id) {
            $mismatchCategory = $delivery->getAdsForSlot(AdCampaign::SLOT_CATEGORY_PAGE, [
                'state' => $this->state->code,
                'category_id' => $this->otherCategory->id,
                'device' => 'mobile',
            ], 5, false);
            $this->assertCount(0, $mismatchCategory);
        }
    }

    public function test_dynamic_listing_feed_injection_respects_configurable_frequency_and_renders_across_pages(): void
    {
        $manager = app(AdCampaignManager::class);
        $delivery = app(AdDeliveryService::class);

        // Configure frequency: 1 ad after every 2 listings, max 3 ads per page
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'ads'])
            ->call('setAdSubTab', 'inventory')
            ->set('placementConfigsForm.' . AdCampaign::SLOT_BETWEEN_LISTINGS . '.insert_every_n', 2)
            ->set('placementConfigsForm.' . AdCampaign::SLOT_BETWEEN_LISTINGS . '.max_ads_per_page', 3)
            ->call('savePlacementConfig', AdCampaign::SLOT_BETWEEN_LISTINGS);

        $config = AdPlacementConfig::forSlot(AdCampaign::SLOT_BETWEEN_LISTINGS)->fresh();
        $this->assertSame(2, $config->insert_every_n);
        $this->assertSame(3, $config->max_ads_per_page);

        // Also update search_results frequency = 2
        AdPlacementConfig::forSlot(AdCampaign::SLOT_SEARCH_RESULTS)->update(['insert_every_n' => 2, 'max_ads_per_page' => 2]);
        $delivery->clearCache();

        // Create 2 distinct active campaigns
        $c1 = $manager->saveCampaign([
            'name' => 'InFeed Promo One',
            'campaign_type' => 'banner',
            'advertiser_name' => 'Sponsor One',
            'destination_url' => 'https://sponsor1.example.com',
            'creative_title' => 'Sponsored Spotlight One',
            'disclosure_label' => 'Sponsored',
            'status' => AdCampaign::STATUS_ACTIVE,
            'priority' => 80,
            'placements' => [
                AdCampaign::SLOT_BETWEEN_LISTINGS,
                AdCampaign::SLOT_SEARCH_RESULTS,
                AdCampaign::SLOT_HOMEPAGE_TOP,
                AdCampaign::SLOT_SIDEBAR,
                AdCampaign::SLOT_CATEGORY_PAGE,
                AdCampaign::SLOT_LOCATION_PAGE,
                AdCampaign::SLOT_LISTING_DETAIL,
            ],
        ], null, $this->admin->id);

        $c2 = $manager->saveCampaign([
            'name' => 'InFeed Affiliate Two',
            'campaign_type' => 'affiliate',
            'affiliate_brand_name' => 'Partner Two',
            'affiliate_tracking_url' => 'https://partner2.example.com/?sub=ldh',
            'creative_title' => 'Affiliate Offer Two',
            'offer_badge' => 'VIP DEAL',
            'disclosure_label' => 'Affiliate Offer',
            'status' => AdCampaign::STATUS_ACTIVE,
            'priority' => 70,
            'placements' => [AdCampaign::SLOT_BETWEEN_LISTINGS, AdCampaign::SLOT_SEARCH_RESULTS],
        ], null, $this->admin->id);

        // Seed 5 active listings
        $listings = collect();
        for ($i = 1; $i <= 5; $i++) {
            $listings->push(Listing::create([
                'user_id' => $this->regularUser->id,
                'category_id' => $this->category->id,
                'state_id' => $this->state->id,
                'city_id' => $this->city->id,
                'title' => "Genuine Local Connection #{$i}",
                'slug' => 'genuine-local-' . $i . '-' . Str::random(6),
                'description' => 'Looking to meet authentic local adults nearby for great conversation.',
                'status' => Listing::STATUS_ACTIVE,
                'poster_age' => 26,
                'poster_gender' => 'woman',
                'intent' => 'dating',
                'published_at' => now(),
            ]));
        }

        // Inject into 5 listings with insert_every_n=2 -> should inject ads after #2 and #4
        $injected = $delivery->injectIntoListings($listings, AdCampaign::SLOT_BETWEEN_LISTINGS, [], false);
        $this->assertCount(7, $injected); // 5 listings + 2 distinct ads
        $this->assertSame('listing', $injected[0]['type']);
        $this->assertSame('listing', $injected[1]['type']);
        $this->assertSame('ad', $injected[2]['type']);
        $this->assertSame('listing', $injected[3]['type']);
        $this->assertSame('listing', $injected[4]['type']);
        $this->assertSame('ad', $injected[5]['type']);
        $this->assertSame('listing', $injected[6]['type']);
        $this->assertNotSame($injected[2]['ad']['campaign_id'], $injected[5]['ad']['campaign_id']);

        // Frontend integration: verify Home, Search, Category, City, and Listing Detail render sponsored content
        $this->withSession(['age_confirmed_18' => true])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Sponsored Spotlight One');

        $this->withSession(['age_confirmed_18' => true])
            ->get(route('search.index'))
            ->assertOk()
            ->assertSee('Sponsored Spotlight One')
            ->assertSee('Affiliate Offer');

        $this->withSession(['age_confirmed_18' => true])
            ->get(route('categories.show', $this->category->slug))
            ->assertOk()
            ->assertSee('Sponsored Spotlight One');

        $this->withSession(['age_confirmed_18' => true])
            ->get(route('locations.city', ['stateCode' => $this->state->code, 'citySlug' => $this->city->slug]))
            ->assertOk()
            ->assertSee('Sponsored Spotlight One');

        $this->withSession(['age_confirmed_18' => true])
            ->get(route('listings.show', $listings->first()->slug))
            ->assertOk()
            ->assertSee('Sponsored Spotlight One');
    }

    public function test_click_fraud_guard_and_verified_s2s_conversion_postback_and_analytics_reporting(): void
    {
        $manager = app(AdCampaignManager::class);

        $campaign = $manager->saveCampaign([
            'name' => 'Conversion Audited Affiliate',
            'campaign_type' => 'affiliate',
            'affiliate_brand_name' => 'TrustedNetwork',
            'affiliate_tracking_url' => 'https://trusted.example.com/offer?id=77',
            'click_id_param' => 'click_id',
            'cpc_dollars' => '1.25',
            'status' => AdCampaign::STATUS_ACTIVE,
            'placements' => [AdCampaign::SLOT_SIDEBAR],
        ], null, $this->admin->id);

        // First human click -> counted and charged CPC ($1.25 = 125 cents)
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) AppleWebKit/537.36 Chrome/120.0'])
            ->get(route('ads.click', ['campaign' => $campaign->uuid, 'slot' => AdCampaign::SLOT_SIDEBAR]))
            ->assertRedirect();

        $this->assertSame(1, $campaign->fresh()->total_clicks);
        $this->assertSame(125, $campaign->fresh()->spent_cents);

        // Rapid duplicate click within 30s -> marked suspicious, NOT counted in total_clicks or spend
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) AppleWebKit/537.36 Chrome/120.0'])
            ->get(route('ads.click', ['campaign' => $campaign->uuid, 'slot' => AdCampaign::SLOT_SIDEBAR]))
            ->assertRedirect();

        $this->assertSame(1, $campaign->fresh()->total_clicks);
        $this->assertSame(125, $campaign->fresh()->spent_cents);
        $this->assertSame(1, AdClick::where('campaign_id', $campaign->id)->where('is_suspicious', true)->count());

        $validClick = AdClick::where('campaign_id', $campaign->id)->where('is_suspicious', false)->firstOrFail();

        // Unauthorized postback rejected with 403
        $this->postJson(route('ads.postback', ['campaign' => $campaign->uuid]), [
            'secret' => 'wrong-secret',
            'click_id' => $validClick->click_token,
            'tx_id' => 'TX-1001',
            'payout' => 25.50,
        ])->assertStatus(403);

        // Valid S2S conversion postback recorded
        $this->postJson(route('ads.postback', ['campaign' => $campaign->uuid]), [
            'secret' => $campaign->postback_secret,
            'click_id' => $validClick->click_token,
            'tx_id' => 'TX-1001',
            'sub_id' => 'ldh_sub',
            'payout' => 25.50,
            'currency' => 'USD',
        ])->assertOk()->assertJson(['ok' => true, 'status' => 'confirmed']);

        // Idempotent repeat callback with same tx_id does not double-count
        $this->postJson(route('ads.postback', ['campaign' => $campaign->uuid]), [
            'secret' => $campaign->postback_secret,
            'click_id' => $validClick->click_token,
            'tx_id' => 'TX-1001',
            'payout' => 25.50,
        ])->assertOk();

        $fresh = $campaign->fresh();
        $this->assertSame(1, $fresh->total_conversions);
        $this->assertSame(2550, $fresh->total_conversion_revenue_cents);
        $this->assertSame(1, AdConversion::where('campaign_id', $campaign->id)->count());

        // Analytics report verification across Today / Last 7 Days
        $report = app(AdAnalyticsService::class)->getReport('today');
        $this->assertSame(1, $report['summary']['clicks']);
        $this->assertSame(1, $report['summary']['conversions']);
        $this->assertSame(25.5, (float) $report['summary']['revenue_usd']);
        $this->assertDatabaseHas('ad_daily_statistics', [
            'campaign_id' => $campaign->id,
            'placement_slot' => AdCampaign::SLOT_SIDEBAR,
            'clicks' => 1,
            'conversions' => 1,
        ]);

        // Test ads:sync-lifecycle command transitions ended campaigns to expired
        $campaign->update(['ends_at' => now()->subMinute()]);
        $this->artisan('ads:sync-lifecycle')->assertExitCode(0);
        $this->assertSame(AdCampaign::STATUS_EXPIRED, $campaign->fresh()->status);
    }
}

