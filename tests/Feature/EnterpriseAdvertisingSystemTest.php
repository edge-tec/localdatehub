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

    public function test_admin_advertising_hub_bulk_actions_filters_slots_and_csv_export(): void
    {
        $manager = app(AdCampaignManager::class);

        $first = $manager->saveCampaign([
            'name' => 'Hub Bulk Alpha',
            'campaign_type' => 'banner',
            'title' => 'Alpha Banner',
            'destination_url' => 'https://example.com/alpha',
            'status' => AdCampaign::STATUS_ACTIVE,
            'placements' => [AdCampaign::SLOT_HOMEPAGE_FEATURED],
        ], null, $this->admin->id);

        $second = $manager->saveCampaign([
            'name' => 'Hub Bulk Beta',
            'campaign_type' => 'affiliate',
            'affiliate_brand_name' => 'BetaBrand',
            'affiliate_tracking_url' => 'https://example.com/beta',
            'status' => AdCampaign::STATUS_DRAFT,
            'placements' => [AdCampaign::SLOT_SIDEBAR],
        ], null, $this->admin->id);

        $hub = Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'ads'])
            ->assertSee('Advertising &amp; Revenue Command Hub', false)
            ->assertSee('Hub Bulk Alpha')
            ->assertSee('Hub Bulk Beta');

        // Filter and resetCampaignFilters
        $hub->set('campaignSearch', 'Alpha')
            ->set('campaignStatusFilter', AdCampaign::STATUS_ACTIVE)
            ->set('campaignTypeFilter', 'banner')
            ->set('campaignSlotFilter', AdCampaign::SLOT_HOMEPAGE_FEATURED)
            ->call('resetCampaignFilters')
            ->assertSet('campaignSearch', '')
            ->assertSet('campaignStatusFilter', 'all')
            ->assertSet('campaignTypeFilter', 'all')
            ->assertSet('campaignSlotFilter', 'all');

        // Bulk status update
        $hub->call('toggleSelectAllCampaigns', [$first->id, $second->id])
            ->assertSet('selectedCampaignIds', [$first->id, $second->id])
            ->call('bulkUpdateCampaignStatus', AdCampaign::STATUS_PAUSED)
            ->assertSet('selectedCampaignIds', []);

        $this->assertSame(AdCampaign::STATUS_PAUSED, $first->fresh()->status);
        $this->assertSame(AdCampaign::STATUS_PAUSED, $second->fresh()->status);

        // Placement group selection & clearCampaignMedia inside modal
        $hub->call('openCampaignModal', $first->id)
            ->call('selectPlacementGroup', 'all')
            ->assertSet('campaignForm.placements', AdCampaign::SLOTS)
            ->call('selectPlacementGroup', 'feeds')
            ->set('campaignForm.desktop_banner_url', 'https://example.com/temp.png')
            ->call('clearCampaignMedia', 'desktop_banner_url')
            ->assertSet('campaignForm.desktop_banner_url', '');

        // Save all placement configs
        $hub->call('setAdSubTab', 'inventory')
            ->set('placementConfigsForm.' . AdCampaign::SLOT_HOMEPAGE_FEATURED . '.insert_every_n', 4)
            ->call('saveAllPlacementConfigs')
            ->assertHasNoErrors();

        $this->assertSame(4, AdPlacementConfig::where('slot', AdCampaign::SLOT_HOMEPAGE_FEATURED)->value('insert_every_n'));

        // Switch to Analytics & export CSV
        $hub->call('setAdSubTab', 'analytics')
            ->call('exportAdAnalyticsCsv')
            ->assertFileDownloaded();

        // Switch to Legacy tab
        $hub->call('setAdSubTab', 'legacy')
            ->assertSet('adSubTab', 'legacy')
            ->assertSee('New Placement');

        // Bulk delete selected campaigns
        $hub->call('setAdSubTab', 'campaigns')
            ->set('selectedCampaignIds', [$second->id])
            ->call('bulkDeleteCampaigns')
            ->assertSet('selectedCampaignIds', []);

        $this->assertNull(AdCampaign::find($second->id));
        $this->assertNotNull(AdCampaign::find($first->id));
    }

    public function test_all_states_target_option_toggles_and_delivers_nationwide(): void
    {
        $secondState = State::where('is_active', true)->where('id', '!=', $this->state->id)->firstOrFail();
        $allStateCodes = State::where('is_active', true)->orderBy('name')->pluck('code')->all();

        $hub = Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'ads'])
            ->call('openCampaignModal')
            ->assertSee('All States (Nationwide)')
            ->call('selectTargetingScope', 'states', 'every')
            ->assertSet('campaignForm.target_states', $allStateCodes)
            ->call('selectTargetingScope', 'states', 'all')
            ->assertSet('campaignForm.target_states', []);

        // Passing 'ALL' alongside state codes treats the state dimension as unrestricted (nationwide)
        $hub->set('campaignForm.name', 'Nationwide State Banner')
            ->set('campaignForm.destination_url', 'https://nationwide.example.com')
            ->set('campaignForm.desktop_banner_url', 'https://cdn.example.com/nationwide.png')
            ->set('campaignForm.placements', [AdCampaign::SLOT_SIDEBAR])
            ->set('campaignForm.target_states', ['ALL', $this->state->code])
            ->call('saveCampaign')
            ->assertHasNoErrors();

        $campaign = AdCampaign::where('name', 'Nationwide State Banner')->with('targetingRules')->firstOrFail();
        $this->assertSame(0, $campaign->targetingRules->where('rule_type', 'state')->count());

        $delivery = app(AdDeliveryService::class);
        $this->assertCount(1, $delivery->getAdsForSlot(AdCampaign::SLOT_SIDEBAR, ['state' => $secondState->code], 5, false));
        $this->assertCount(1, $delivery->getAdsForSlot(AdCampaign::SLOT_SIDEBAR, ['state' => $this->state->code], 5, false));
    }

    public function test_active_banner_campaign_with_targeting_and_session_cap_delivers_across_all_placements_and_api(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('ad-banners/uber-desktop.jpg', 'fake-jpg');
        Storage::disk('public')->put('ad-banners/uber-mobile.jpg', 'fake-jpg');

        $otherState = State::where('is_active', true)->where('id', '!=', $this->state->id)->firstOrFail();

        $manager = app(AdCampaignManager::class);
        $campaign = $manager->saveCampaign([
            'name' => 'Uberhorny',
            'campaign_type' => AdCampaign::TYPE_BANNER,
            'advertiser_name' => 'Uberhorny',
            'creative_title' => 'Looking for Exciting New Connections?',
            'creative_description' => 'Discover new people, explore exciting connections today!',
            'cta_text' => 'Join Now',
            'destination_url' => 'https://shroo.link/FcQEM',
            'desktop_banner_url' => '/storage/ad-banners/uber-desktop.jpg',
            'mobile_banner_url' => '/storage/ad-banners/uber-mobile.jpg',
            'alt_text' => 'Uberhorny Banner',
            'disclosure_label' => 'Sponsored',
            'status' => AdCampaign::STATUS_ACTIVE,
            'priority' => 10,
            'weight' => 100,
            'frequency_cap_per_session' => 2,
            'starts_at' => now()->subHour()->toDateTimeString(),
            'ends_at' => now()->addDays(14)->toDateTimeString(),
            'placements' => [
                AdCampaign::SLOT_BETWEEN_LISTINGS,
                AdCampaign::SLOT_HOMEPAGE_FEATURED,
                AdCampaign::SLOT_SIDEBAR,
                AdCampaign::SLOT_LISTING_DETAIL,
                AdCampaign::SLOT_CATEGORY_PAGE,
                AdCampaign::SLOT_SEARCH_RESULTS,
                AdCampaign::SLOT_LOCATION_PAGE,
                AdCampaign::SLOT_HOMEPAGE_TOP,
            ],
            // 4 targeting rules mirroring real-world multi-dimension campaign targeting
            'target_countries' => ['US'],
            'target_states' => [$this->state->code],
            'target_categories' => [(string) $this->category->id],
            'target_devices' => ['desktop', 'mobile'],
        ], null, $this->admin->id);

        // Seed only 2 active listings (< default insert_every_n = 6) to also prove sparse feed injection works
        $listing = null;
        for ($i = 1; $i <= 2; $i++) {
            $listing = Listing::create([
                'user_id' => $this->regularUser->id,
                'category_id' => $this->category->id,
                'state_id' => $this->state->id,
                'city_id' => $this->city->id,
                'title' => "Local Verified Connection #{$i}",
                'slug' => 'local-verified-' . $i . '-' . Str::random(6),
                'description' => 'Authentic local singles connecting privately.',
                'status' => Listing::STATUS_ACTIVE,
                'is_featured' => true,
                'poster_age' => 27,
                'poster_gender' => 'woman',
                'intent' => 'dating',
                'published_at' => now(),
            ]);
        }

        $browserHeaders = ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36'];

        // 1. Public JSON API returns active banner without auth, with no-cache headers
        $apiResponse = $this->withHeaders($browserHeaders)
            ->getJson(route('ads.api.serve', [
                'placement' => AdCampaign::SLOT_BETWEEN_LISTINGS,
                'device' => 'desktop',
            ]));
        $apiResponse->assertOk()
            ->assertJson([
                'ok' => true,
                'slot' => AdCampaign::SLOT_BETWEEN_LISTINGS,
                'device' => 'desktop',
                'count' => 1,
            ])
            ->assertJsonPath('ads.0.campaign_name', 'Uberhorny')
            ->assertJsonPath('ads.0.desktop_banner_url', '/storage/ad-banners/uber-desktop.jpg')
            ->assertJsonPath('ads.0.mobile_banner_url', '/storage/ad-banners/uber-mobile.jpg');

        $cacheHeader = (string) $apiResponse->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheHeader);
        $this->assertStringContainsString('no-cache', $cacheHeader);

        // Mobile device query selects mobile banner URL cleanly
        $this->getJson(route('ads.api.serve', ['placement' => AdCampaign::SLOT_HOMEPAGE_TOP, 'device' => 'mobile']))
            ->assertOk()
            ->assertJsonPath('ads.0.image_url', '/storage/ad-banners/uber-mobile.jpg');

        // Invalid slot returns structured 422 JSON (never 500)
        $this->getJson(route('ads.api.serve', ['placement' => 'non_existent_slot']))
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        // 2. Frontend pages render the banner publicly across Homepage, Category, Search, State/City, and Listing Detail
        $this->withSession(['age_confirmed_18' => true])
            ->withHeaders($browserHeaders)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Looking for Exciting New Connections?')
            ->assertSee('/storage/ad-banners/uber-desktop.jpg')
            ->assertSee('/storage/ad-banners/uber-mobile.jpg');

        // Page 2 in same session still serves (within 2/sess frequency cap)
        $this->withSession(['age_confirmed_18' => true])
            ->withHeaders($browserHeaders)
            ->get(route('listings.show', $listing->slug))
            ->assertOk()
            ->assertSee('Looking for Exciting New Connections?');

        // Page 3 in the SAME session hits the 2/sess cap and stops showing
        $this->withHeaders($browserHeaders)
            ->get(route('search.index'))
            ->assertOk()
            ->assertDontSee('Looking for Exciting New Connections?');

        // Fresh visitor sessions verify delivery across Search, Category & State/City pages
        $this->withSession(['age_confirmed_18' => true, 'ads_seen_counts' => []])
            ->withHeaders($browserHeaders)
            ->get(route('search.index'))
            ->assertOk()
            ->assertSee('Looking for Exciting New Connections?');

        $this->withSession(['age_confirmed_18' => true, 'ads_seen_counts' => []])
            ->withHeaders($browserHeaders)
            ->get(route('categories.show', $this->category->slug))
            ->assertOk()
            ->assertSee('Looking for Exciting New Connections?');

        $this->withSession(['age_confirmed_18' => true, 'ads_seen_counts' => []])
            ->withHeaders($browserHeaders)
            ->get(route('locations.state', ['stateCode' => $this->state->code]))
            ->assertOk()
            ->assertSee('Looking for Exciting New Connections?');

        $this->withSession(['age_confirmed_18' => true, 'ads_seen_counts' => []])
            ->withHeaders($browserHeaders)
            ->get(route('locations.city', ['stateCode' => $this->state->code, 'citySlug' => $this->city->slug]))
            ->assertOk()
            ->assertSee('Looking for Exciting New Connections?');

        // Mismatched state is strictly blocked when state context is present
        $delivery = app(AdDeliveryService::class);
        $this->assertCount(0, $delivery->getAdsForSlot(AdCampaign::SLOT_LOCATION_PAGE, ['state' => $otherState->code, 'device' => 'desktop'], 5, false));

        // 3. Beacon impression & outbound click tracking work cleanly without CSRF blocks
        $this->withHeaders($browserHeaders)
            ->postJson(route('ads.impression'), [
                'campaign_uuid' => $campaign->uuid,
                'slot' => AdCampaign::SLOT_HOMEPAGE_FEATURED,
                'state' => $this->state->code,
            ])
            ->assertOk()
            ->assertJsonStructure(['recorded']);

        $this->withHeaders($browserHeaders)
            ->get(route('ads.click', ['campaign' => $campaign->uuid, 'slot' => AdCampaign::SLOT_HOMEPAGE_FEATURED]))
            ->assertRedirect();

        $fresh = $campaign->fresh();
        $this->assertGreaterThanOrEqual(1, $fresh->total_impressions);
        $this->assertSame(1, $fresh->total_clicks);
    }

    public function test_weighted_rotation_and_realtime_campaign_diagnostics_and_quick_fixes(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('ad-banners/heavy.jpg', 'fake-jpg');
        Storage::disk('public')->put('ad-banners/light.jpg', 'fake-jpg');

        $manager = app(AdCampaignManager::class);
        $delivery = app(AdDeliveryService::class);

        $heavy = $manager->saveCampaign([
            'name' => 'Heavy Weight Banner',
            'campaign_type' => 'banner',
            'destination_url' => 'https://heavy.example.com',
            'desktop_banner_url' => '/storage/ad-banners/heavy.jpg',
            'status' => AdCampaign::STATUS_ACTIVE,
            'priority' => 10,
            'weight' => 90,
            'placements' => [AdCampaign::SLOT_SIDEBAR],
        ], null, $this->admin->id);

        $light = $manager->saveCampaign([
            'name' => 'Light Weight Banner',
            'campaign_type' => 'banner',
            'destination_url' => 'https://light.example.com',
            'desktop_banner_url' => '/storage/ad-banners/light.jpg',
            'status' => AdCampaign::STATUS_ACTIVE,
            'priority' => 10,
            'weight' => 10,
            'placements' => [AdCampaign::SLOT_SIDEBAR],
        ], null, $this->admin->id);

        // Over 40 draws of 1 slot, both appear and heavy dominates significantly
        $wins = [$heavy->id => 0, $light->id => 0];
        for ($i = 0; $i < 40; $i++) {
            $picked = $delivery->getAdsForSlot(AdCampaign::SLOT_SIDEBAR, ['device' => 'desktop'], 1, false);
            $this->assertCount(1, $picked);
            $wins[$picked->first()['campaign_id']]++;
        }
        $this->assertGreaterThan($wins[$light->id], $wins[$heavy->id]);

        // Diagnostic check on live eligible campaign
        $diagLive = $delivery->diagnoseCampaign($heavy);
        $this->assertTrue($diagLive['eligible']);
        $this->assertSame('ELIGIBLE', $diagLive['eligibility_label']);
        $this->assertSame('LIVE DELIVERING', $diagLive['delivery_status']);
        $this->assertTrue($diagLive['api_delivery']['overall_ok']);
        $this->assertTrue($diagLive['creative']['desktop_file_ok']);

        // Future starts_at + status active shows clear blocking reason & immediate fix button works in Admin UI
        $heavy->update(['starts_at' => now()->addHours(4)]);
        $diagFuture = $delivery->diagnoseCampaign($heavy->fresh(['creatives', 'placements', 'targetingRules']));
        $this->assertFalse($diagFuture['eligible']);
        $this->assertSame('NOT ELIGIBLE', $diagFuture['eligibility_label']);
        $this->assertNotEmpty($diagFuture['blocking_reasons']);

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'ads'])
            ->call('openDiagnosticModal', $heavy->id)
            ->assertSet('showDiagnosticModal', true)
            ->assertSee('Real-Time Ad Delivery Diagnostics')
            ->assertSee('NOT ELIGIBLE')
            ->call('startCampaignImmediately', $heavy->id)
            ->assertSet('diagnosticData.eligible', true)
            ->assertSet('diagnosticData.delivery_status', 'LIVE DELIVERING');
    }
}

