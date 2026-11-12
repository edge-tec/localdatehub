<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Services\FiveCountryGeographicSeederService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FiveCountryMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private User $poster;

    protected function setUp(): void
    {
        parent::setUp();

        app(FiveCountryGeographicSeederService::class)->seedAll();

        $this->category = Category::query()->create([
            'name' => 'Casual Encounters',
            'slug' => 'casual-encounters',
            'description' => 'Local dating and casual connections',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $this->poster = User::factory()->create([
            'name' => 'Verified Advertiser',
            'email' => 'advertiser@example.com',
        ]);
    }

    public function test_seeder_populates_exact_five_countries_and_their_administrative_hierarchies(): void
    {
        $codes = Country::query()
            ->whereIn('code', Country::SIX_MARKETPLACES)
            ->orderByRaw("CASE code WHEN 'US' THEN 1 WHEN 'GB' THEN 2 WHEN 'CA' THEN 3 WHEN 'AU' THEN 4 WHEN 'IE' THEN 5 WHEN 'NZ' THEN 6 ELSE 99 END")
            ->pluck('code')
            ->all();

        $this->assertSame(['US', 'GB', 'CA', 'AU', 'IE', 'NZ'], $codes);

        // 0. United States (Primary): 50 states + DC (+ territories)
        $us = Country::query()->where('code', 'US')->firstOrFail();
        $this->assertSame('USD', $us->currency_code);
        $this->assertSame('$', $us->currency_symbol);
        $this->assertGreaterThanOrEqual(51, State::query()->where('country_id', $us->id)->count());

        // 1. UK: 13 nations & official regions (England + 9 English regions + Scotland, Wales, Northern Ireland)
        $gb = Country::query()->where('code', 'GB')->firstOrFail();
        $this->assertSame('GBP', $gb->currency_code);
        $this->assertSame('£', $gb->currency_symbol);
        $this->assertGreaterThanOrEqual(13, State::query()->where('country_id', $gb->id)->count());
        $this->assertGreaterThanOrEqual(150, City::query()->where('country_id', $gb->id)->count());

        // 2. Canada: 10 provinces + 3 territories = 13
        $ca = Country::query()->where('code', 'CA')->firstOrFail();
        $this->assertSame('CAD', $ca->currency_code);
        $this->assertSame('C$', $ca->currency_symbol);
        $this->assertSame(13, State::query()->where('country_id', $ca->id)->count());
        $this->assertGreaterThanOrEqual(150, City::query()->where('country_id', $ca->id)->count());

        // 3. Australia: 6 states + 2 territories = 8
        $au = Country::query()->where('code', 'AU')->firstOrFail();
        $this->assertSame('AUD', $au->currency_code);
        $this->assertSame('A$', $au->currency_symbol);
        $this->assertSame(8, State::query()->where('country_id', $au->id)->count());

        // 4. Republic of Ireland: 26 counties (Northern Ireland is in GB, never IE)
        $ie = Country::query()->where('code', 'IE')->firstOrFail();
        $this->assertSame('EUR', $ie->currency_code);
        $this->assertSame('€', $ie->currency_symbol);
        $this->assertSame(26, State::query()->where('country_id', $ie->id)->where('administrative_level', 'county')->count());
        $this->assertFalse(State::query()->where('country_id', $ie->id)->where('slug', 'northern-ireland')->exists());

        // 5. New Zealand: 16 regions
        $nz = Country::query()->where('code', 'NZ')->firstOrFail();
        $this->assertSame('NZD', $nz->currency_code);
        $this->assertSame('NZ$', $nz->currency_symbol);
        $this->assertSame(16, State::query()->where('country_id', $nz->id)->count());
    }

    public function test_united_kingdom_marketplace_loads_nations_cities_listings_and_gbp_currency(): void
    {
        $gb = Country::query()->where('code', 'GB')->firstOrFail();
        $england = State::query()->where('country_id', $gb->id)->where('slug', 'england')->firstOrFail();
        $london = City::query()->where('state_id', $england->id)->where('slug', 'london')->firstOrFail();

        $ca = Country::query()->where('code', 'CA')->firstOrFail();
        $ontario = State::query()->where('country_id', $ca->id)->where('slug', 'ontario')->firstOrFail();
        $toronto = City::query()->where('state_id', $ontario->id)->where('slug', 'toronto')->firstOrFail();

        $ukListing = $this->createListingForCity($london, 'London West End Connection', 120.00);
        $caListing = $this->createListingForCity($toronto, 'Downtown Toronto Meetup', 150.00);

        // /gb homepage uses main welcome dashboard design without country list
        $homeResp = $this->get('/gb');
        $homeResp->assertOk();
        $homeResp->assertSee('Privacy-First Adult Dating & Personals');
        $homeResp->assertDontSee('Switch Country:');
        $homeResp->assertSee('United Kingdom');
        $homeResp->assertSee('England');
        $homeResp->assertSee('Scotland');
        $homeResp->assertSee('Wales');
        $homeResp->assertSee('Northern Ireland');
        $homeResp->assertSee($ukListing->title);
        $homeResp->assertSee('£120');
        $homeResp->assertDontSee($caListing->title);

        // /gb/england area page
        $areaResp = $this->get('/gb/england');
        $areaResp->assertOk();
        $areaResp->assertSee('England, United Kingdom');
        $areaResp->assertSee('London');
        $areaResp->assertSee('Manchester');
        $areaResp->assertSee($ukListing->title);
        $areaResp->assertDontSee($caListing->title);

        // /gb/england/london city page
        $cityResp = $this->get('/gb/england/london');
        $cityResp->assertOk();
        $cityResp->assertSee('London, England Classifieds');
        $cityResp->assertSee($ukListing->title);
        $cityResp->assertDontSee($caListing->title);
    }

    public function test_canada_marketplace_loads_provinces_territories_cities_listings_and_cad_currency(): void
    {
        $ca = Country::query()->where('code', 'CA')->firstOrFail();
        $ontario = State::query()->where('country_id', $ca->id)->where('slug', 'ontario')->firstOrFail();
        $toronto = City::query()->where('state_id', $ontario->id)->where('slug', 'toronto')->firstOrFail();

        $au = Country::query()->where('code', 'AU')->firstOrFail();
        $nsw = State::query()->where('country_id', $au->id)->where('slug', 'new-south-wales')->firstOrFail();
        $sydney = City::query()->where('state_id', $nsw->id)->where('slug', 'sydney')->firstOrFail();

        $caListing = $this->createListingForCity($toronto, 'Toronto Harbourfront Date', 95.00);
        $auListing = $this->createListingForCity($sydney, 'Sydney Harbour Evening', 110.00);

        $homeResp = $this->get('/ca');
        $homeResp->assertOk();
        $homeResp->assertSee('Privacy-First Adult Dating & Personals');
        $homeResp->assertDontSee('Switch Country:');
        $homeResp->assertSee('Canada');
        $homeResp->assertSee('Ontario');
        $homeResp->assertSee('Quebec');
        $homeResp->assertSee('British Columbia');
        $homeResp->assertSee($caListing->title);
        $homeResp->assertSee('C$95');
        $homeResp->assertDontSee($auListing->title);

        $this->get('/ca/ontario')->assertOk()->assertSee('Toronto')->assertSee($caListing->title);
        $this->get('/ca/ontario/toronto')->assertOk()->assertSee($caListing->title)->assertDontSee($auListing->title);
    }

    public function test_australia_marketplace_loads_states_territories_cities_listings_and_aud_currency(): void
    {
        $au = Country::query()->where('code', 'AU')->firstOrFail();
        $victoria = State::query()->where('country_id', $au->id)->where('slug', 'victoria')->firstOrFail();
        $melbourne = City::query()->where('state_id', $victoria->id)->where('slug', 'melbourne')->firstOrFail();

        $auListing = $this->createListingForCity($melbourne, 'Melbourne Southbank Connection', 140.00);

        $homeResp = $this->get('/au');
        $homeResp->assertOk();
        $homeResp->assertSee('Privacy-First Adult Dating & Personals');
        $homeResp->assertDontSee('Switch Country:');
        $homeResp->assertSee('Australia');
        $homeResp->assertSee('New South Wales');
        $homeResp->assertSee('Victoria');
        $homeResp->assertSee('Queensland');
        $homeResp->assertSee('Western Australia');
        $homeResp->assertSee('South Australia');
        $homeResp->assertSee('Tasmania');
        $homeResp->assertSee('Australian Capital Territory');
        $homeResp->assertSee('Northern Territory');
        $homeResp->assertSee('Sydney');
        $homeResp->assertSee('Melbourne');
        $homeResp->assertSee('Brisbane');
        $homeResp->assertSee('Perth');
        $homeResp->assertSee('Adelaide');
        $homeResp->assertSee('Hobart');
        $homeResp->assertSee('Canberra');
        $homeResp->assertSee('Darwin');
        $homeResp->assertSee($auListing->title);
        $homeResp->assertSee('A$140');

        $this->assertGreaterThanOrEqual(200, City::query()->where('country_id', $au->id)->count());

        $nswApi = $this->getJson('/api/locations/states/nsw/cities?country=au');
        $nswApi->assertOk()->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(45, count($nswApi->json('data.cities')));

        $this->get('/au/victoria')->assertOk()->assertSee('Melbourne')->assertSee($auListing->title);
        $this->get('/au/victoria/melbourne')->assertOk()->assertSee($auListing->title);
    }

    public function test_ireland_marketplace_represents_republic_of_ireland_and_never_mixes_northern_ireland(): void
    {
        $ie = Country::query()->where('code', 'IE')->firstOrFail();
        $corkCounty = State::query()->where('country_id', $ie->id)->where('slug', 'county-cork')->firstOrFail();
        $corkCity = City::query()->where('state_id', $corkCounty->id)->where('slug', 'cork')->firstOrFail();

        $gb = Country::query()->where('code', 'GB')->firstOrFail();
        $northernIreland = State::query()->where('country_id', $gb->id)->where('slug', 'northern-ireland')->firstOrFail();
        $belfast = City::query()->where('state_id', $northernIreland->id)->where('slug', 'belfast')->firstOrFail();

        $ieListing = $this->createListingForCity($corkCity, 'Cork City Centre Social', 80.00);
        $niListing = $this->createListingForCity($belfast, 'Belfast Cathedral Quarter Ad', 85.00);

        // /ie must show Republic of Ireland listing in EUR (€) and NEVER show Northern Ireland (Belfast) listing
        $ieResp = $this->get('/ie');
        $ieResp->assertOk();
        $ieResp->assertSee('Privacy-First Adult Dating & Personals');
        $ieResp->assertDontSee('Switch Country:');
        $ieResp->assertSee('Ireland');
        $ieResp->assertSee('County Cork');
        $ieResp->assertSee('County Dublin');
        $ieResp->assertSee($ieListing->title);
        $ieResp->assertSee('€80');
        $ieResp->assertDontSee($niListing->title);

        // /ie/northern-ireland must 404 because Northern Ireland is under /gb/northern-ireland
        $this->get('/ie/northern-ireland')->assertNotFound();
        $this->get('/gb/northern-ireland/belfast')
            ->assertOk()
            ->assertSee($niListing->title)
            ->assertDontSee($ieListing->title);

        // /ie/county-cork/cork works and shows Cork listing
        $this->get('/ie/county-cork/cork')
            ->assertOk()
            ->assertSee($ieListing->title)
            ->assertDontSee($niListing->title);
    }

    public function test_new_zealand_marketplace_loads_regions_cities_listings_and_nzd_currency(): void
    {
        $nz = Country::query()->where('code', 'NZ')->firstOrFail();
        $aucklandRegion = State::query()->where('country_id', $nz->id)->where('slug', 'auckland')->firstOrFail();
        $aucklandCity = City::query()->where('state_id', $aucklandRegion->id)->where('slug', 'auckland')->firstOrFail();

        $nzListing = $this->createListingForCity($aucklandCity, 'Auckland Viaduct Harbour Meetup', 130.00);

        $nzResp = $this->get('/nz');
        $nzResp->assertOk();
        $nzResp->assertSee('Privacy-First Adult Dating & Personals');
        $nzResp->assertDontSee('Switch Country:');
        $nzResp->assertSee('New Zealand');
        $nzResp->assertSee('Auckland');
        $nzResp->assertSee('Wellington');
        $nzResp->assertSee('Canterbury');
        $nzResp->assertSee($nzListing->title);
        $nzResp->assertSee('NZ$130');

        $this->get('/nz/auckland/auckland')->assertOk()->assertSee($nzListing->title);
    }

    public function test_geoip_detection_redirects_supported_countries_and_sends_unsupported_to_choose_country(): void
    {
        // Supported countries redirect directly to their respective marketplaces (/us, /gb, /ca, /au, /ie, /nz)
        $this->withHeader('CF-IPCountry', 'US')->get('/')->assertRedirect('/us');
        $this->withHeader('CF-IPCountry', 'GB')->get('/')->assertRedirect('/gb');
        $this->withHeader('CF-IPCountry', 'CA')->get('/')->assertRedirect('/ca');
        $this->withHeader('CF-IPCountry', 'AU')->get('/')->assertRedirect('/au');
        $this->withHeader('CF-IPCountry', 'IE')->get('/')->assertRedirect('/ie');
        $this->withHeader('CF-IPCountry', 'NZ')->get('/')->assertRedirect('/nz');

        // Any other country IP falls back directly to /us main dashboard without showing a country list
        $this->withHeader('CF-IPCountry', 'DE')->get('/')->assertRedirect('/us');
        $this->get('/us')->assertOk()->assertSee('Privacy-First Adult Dating & Personals');
    }

    public function test_search_engine_crawlers_are_never_force_redirected(): void
    {
        $crawlerResp = $this->withHeaders([
            'CF-IPCountry' => 'GB',
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ])->get('/');

        $crawlerResp->assertOk();
    }

    public function test_manual_country_switcher_persists_selection_and_overrides_geoip(): void
    {
        // Visitor switches to Canada
        $switchResp = $this->get('/switch-country/ca');
        $switchResp->assertRedirect('/ca');
        $switchResp->assertCookie('ldh_country', 'CA');

        // Even if IP says GB, manual selection in session keeps visitor on /ca
        $this->withHeader('CF-IPCountry', 'GB')->get('/')->assertRedirect('/ca');
    }

    public function test_cascading_location_apis_and_country_sitemaps_work_for_all_five_countries(): void
    {
        $countriesApi = $this->getJson('/api/locations/countries');
        $countriesApi->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('data.0.code', 'US');

        $usAreasApi = $this->getJson('/api/locations/countries/US/areas');
        $usAreasApi->assertOk()
            ->assertJsonPath('country.code', 'US');
        $this->assertGreaterThanOrEqual(51, count($usAreasApi->json('data')));

        $gbAreasApi = $this->getJson('/api/locations/countries/GB/areas');
        $gbAreasApi->assertOk()
            ->assertJsonPath('country.code', 'GB');
        $this->assertGreaterThanOrEqual(13, count($gbAreasApi->json('data')));

        $englandCitiesApi = $this->getJson('/api/locations/countries/GB/areas/england/cities');
        $englandCitiesApi->assertOk()
            ->assertJsonPath('country.code', 'GB')
            ->assertJsonPath('area.slug', 'england');

        foreach (['us', 'gb', 'ca', 'au', 'ie', 'nz'] as $slug) {
            $sitemap = $this->get("/{$slug}/sitemap.xml");
            $sitemap->assertOk();
            $this->assertStringContainsString('<urlset', $sitemap->getContent());
            $this->assertStringContainsString("/{$slug}", $sitemap->getContent());
        }
    }

    public function test_united_states_primary_marketplace_loads_50_states_dc_cities_listings_and_usd(): void
    {
        $us = Country::query()->where('code', 'US')->firstOrFail();
        $this->assertSame('USD', $us->currency_code);
        $this->assertSame('$', $us->currency_symbol);
        $this->assertGreaterThanOrEqual(51, State::query()->where('country_id', $us->id)->count());

        $fl = State::query()->where('country_id', $us->id)->where('code', 'FL')->firstOrFail();
        $miami = City::query()->where('state_id', $fl->id)->where('slug', 'miami')->firstOrFail();

        $caState = State::query()->where('country_id', $us->id)->where('code', 'CA')->firstOrFail();
        $la = City::query()->where('state_id', $caState->id)->where('slug', 'los-angeles')->firstOrFail();

        $nyState = State::query()->where('country_id', $us->id)->where('code', 'NY')->firstOrFail();
        $nyc = City::query()->where('state_id', $nyState->id)->where('slug', 'new-york')->first()
            ?? City::query()->where('state_id', $nyState->id)->firstOrFail();

        $txState = State::query()->where('country_id', $us->id)->where('code', 'TX')->firstOrFail();
        $houston = City::query()->where('state_id', $txState->id)->where('slug', 'houston')->firstOrFail();

        $ilState = State::query()->where('country_id', $us->id)->where('code', 'IL')->firstOrFail();
        $chicago = City::query()->where('state_id', $ilState->id)->where('slug', 'chicago')->firstOrFail();

        $usListing = $this->createListingForCity($miami, 'Miami South Beach Connection', 150.00);
        $gb = Country::query()->where('code', 'GB')->firstOrFail();
        $england = State::query()->where('country_id', $gb->id)->where('slug', 'england')->firstOrFail();
        $london = City::query()->where('state_id', $england->id)->where('slug', 'london')->firstOrFail();
        $ukListing = $this->createListingForCity($london, 'London West End Listing', 110.00);

        $usHome = $this->get('/us');
        $usHome->assertOk();
        $usHome->assertSee('Privacy-First Adult Dating & Personals');
        $usHome->assertSee('United States');
        $usHome->assertSee('California');
        $usHome->assertSee('Florida');
        $usHome->assertSee('Texas');
        $usHome->assertSee('New York');
        $usHome->assertSee('District of Columbia');
        $usHome->assertSee($usListing->title);
        $usHome->assertDontSee($ukListing->title);

        $this->get('/us/florida/miami')->assertOk()->assertSee($usListing->title);
        $this->get('/us/california/los-angeles')->assertOk();
        $this->get('/us/texas/houston')->assertOk();
        $this->get('/us/illinois/chicago')->assertOk();
        $this->assertNotNull($nyc);

        // Controlled GeoIP region/city headers resolve Florida -> Miami on /us
        $this->withHeaders([
            'CF-IPCountry' => 'US',
            'X-Region-Name' => 'Florida',
            'X-City-Name' => 'Miami',
        ])->get('/us')->assertOk()->assertSee($usListing->title);

        // Admin-configured branding images (hero_image_url, sidebar_promo_image_url, site_logo_url) apply on country pages
        \App\Modules\Admin\Models\SystemSetting::updateOrCreate(
            ['key' => 'hero_image_url'],
            ['value' => '/storage/branding/custom-hero-test.jpg', 'group' => 'branding', 'type' => 'string', 'is_public' => true]
        );
        \App\Modules\Admin\Models\SystemSetting::updateOrCreate(
            ['key' => 'sidebar_promo_image_url'],
            ['value' => '/storage/branding/custom-promo-test.jpg', 'group' => 'branding', 'type' => 'string', 'is_public' => true]
        );
        \App\Modules\Admin\Models\SystemSetting::updateOrCreate(
            ['key' => 'site_logo_url'],
            ['value' => '/storage/branding/custom-logo-test.png', 'group' => 'branding', 'type' => 'string', 'is_public' => true]
        );

        $this->get('/us')
            ->assertOk()
            ->assertSee('custom-hero-test.jpg')
            ->assertSee('custom-promo-test.jpg')
            ->assertSee('custom-logo-test.png');
    }

    public function test_admin_can_manage_country_marketplaces_currencies_categories_and_locations(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/admin/countries')
            ->assertOk()
            ->assertSee('Six-Country Marketplace Management')
            ->assertSee('United States (US)')
            ->assertSee('Primary')
            ->assertSee('United Kingdom (GB)')
            ->assertSee('New Zealand (NZ)');

        $this->actingAs($admin)
            ->get('/admin/countries/us')
            ->assertOk()
            ->assertSee('United States Marketplace Configuration');

        $this->actingAs($admin)
            ->get('/admin/countries/gb')
            ->assertOk()
            ->assertSee('United Kingdom Marketplace Configuration');

        $this->actingAs($admin)
            ->put('/admin/countries/gb', [
                'name' => 'United Kingdom',
                'currency_code' => 'GBP',
                'currency_symbol' => '£',
                'division_label' => 'Nation',
                'timezone' => 'Europe/London',
                'seo_title' => 'UK Classifieds Custom SEO Title',
                'seo_description' => 'Custom UK SEO Description',
                'is_active' => '1',
                'is_marketplace_enabled' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('countries', [
            'code' => 'GB',
            'seo_title' => 'UK Classifieds Custom SEO Title',
        ]);
    }

    public function test_public_countries_directory_page_lists_all_six_countries(): void
    {
        $response = $this->get('/countries');

        $response->assertOk()
            ->assertSee('data-choose-country-page', false)
            ->assertSee('United States')
            ->assertSee('United Kingdom')
            ->assertSee('Canada')
            ->assertSee('Australia')
            ->assertSee('Ireland')
            ->assertSee('New Zealand');
    }

    private function createListingForCity(City $city, string $title, float $price): Listing
    {
        return Listing::query()->create([
            'user_id' => $this->poster->id,
            'category_id' => $this->category->id,
            'country_id' => $city->country_id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::lower(Str::random(5)),
            'description' => $title . ' full listing description with verified local details.',
            'poster_age' => 26,
            'poster_gender' => 'Woman',
            'seeking_gender' => 'Man',
            'price' => $price,
            'status' => 'active',
            'is_featured' => true,
            'published_at' => now()->subMinutes(10),
            'expires_at' => now()->addDays(30),
        ]);
    }
}
