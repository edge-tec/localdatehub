<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\Permission;
use App\Modules\Admin\Models\Role;
use App\Modules\Admin\Models\SystemSetting;
use App\Modules\Listings\Models\SearchTrend;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\Models\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSeoMarketingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::create([
            'name' => 'United States',
            'code' => 'US',
            'iso3' => 'USA',
            'phone_code' => '+1',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $role = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'is_system' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage SEO & System',
            'slug' => 'manage_settings',
            'module' => 'system',
        ]);

        $role->permissions()->attach($permission->id);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->admin->roles()->attach($role->id);
    }

    public function test_admin_can_view_seo_and_marketing_command_center_with_kpi_cards()
    {
        SeoMeta::create([
            'page_route' => 'home',
            'meta_title' => 'LocalDateHub — Authentic Adult Personals',
            'meta_description' => 'Browse verified local singles and adult personal classifieds.',
            'robots_directive' => 'index, follow',
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class, ['initialTab' => 'seo'])
            ->assertSee('Indexed Footprint')
            ->assertSee('SERP Health Score')
            ->assertSee('Crawl Directives')
            ->assertSee('Organic Search Hits')
            ->assertSee('Page Metadata &amp; Overrides', false)
            ->assertSee('XML Sitemaps Engine')
            ->assertSee('Robots.txt &amp; Crawlers', false)
            ->assertSee('Live Google SERP Simulator')
            ->assertSee('Tracking Pixels &amp; Tags', false)
            ->assertSee('LocalDateHub — Authentic Adult Personals');
    }

    public function test_admin_can_generate_recommended_baseline_seo_metas()
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class, ['initialTab' => 'seo'])
            ->call('generateDefaultSeoMetas');

        $this->assertDatabaseHas('seo_metas', ['page_route' => 'home']);
        $this->assertDatabaseHas('seo_metas', ['page_route' => 'search']);
        $this->assertDatabaseHas('seo_metas', ['page_route' => 'locations']);
        $this->assertDatabaseHas('seo_metas', ['page_route' => 'categories']);
    }

    public function test_admin_can_create_update_and_delete_seo_override()
    {
        $comp = Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class, ['initialTab' => 'seo'])
            ->call('openSeoModal')
            ->assertSet('showSeoModal', true)
            ->set('seoForm.page_route', '/locations/tx/austin')
            ->set('seoForm.meta_title', 'Austin Adult Dating & Personals | LocalDateHub')
            ->set('seoForm.meta_description', 'Discover verified local classifieds and dating encounters in Austin Texas.')
            ->set('seoForm.meta_keywords', 'austin dating, texas personals')
            ->set('seoForm.robots_directive', 'index, follow')
            ->set('seoForm.canonical_url', 'https://localdatehub.com/locations/tx/austin')
            ->set('seoForm.og_image_url', 'https://localdatehub.com/images/og/austin.jpg')
            ->call('saveSeo')
            ->assertSet('showSeoModal', false);

        $seo = SeoMeta::where('page_route', '/locations/tx/austin')->first();
        $this->assertNotNull($seo);
        $this->assertEquals('Austin Adult Dating & Personals | LocalDateHub', $seo->meta_title);
        $this->assertEquals('https://localdatehub.com/images/og/austin.jpg', $seo->og_image_url);

        // Update
        $comp->call('openSeoModal', $seo->id)
            ->assertSet('editingSeoId', $seo->id)
            ->set('seoForm.meta_title', 'Updated Austin Dating Classifieds')
            ->call('saveSeo');

        $this->assertEquals('Updated Austin Dating Classifieds', $seo->fresh()->meta_title);

        // Delete
        $comp->call('deleteSeo', $seo->id);

        $this->assertDatabaseMissing('seo_metas', ['id' => $seo->id]);
    }

    public function test_admin_can_inspect_sitemaps_and_purge_cache()
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class, ['initialTab' => 'seo'])
            ->call('setSeoSubTab', 'sitemaps')
            ->assertSet('seoSubTab', 'sitemaps')
            ->assertSee('Master Sitemap Index')
            ->assertSee('Classified Categories Sitemap')
            ->assertSee('Geo Locations (50 States &amp; Cities)', false)
            ->assertSee('Live Classified Listings')
            ->call('refreshSitemaps');
    }

    public function test_admin_can_save_marketing_tracking_tags()
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class, ['initialTab' => 'seo'])
            ->call('setSeoSubTab', 'marketing')
            ->set('marketingGaId', 'G-TEST123456')
            ->set('marketingGtmId', 'GTM-TEST999')
            ->set('marketingPixelId', '987654321098765')
            ->set('marketingCustomHead', '<script>console.log("analytics initialized");</script>')
            ->call('saveMarketingSettings');

        $this->assertEquals('G-TEST123456', SystemSetting::get('marketing_ga_id'));
        $this->assertEquals('GTM-TEST999', SystemSetting::get('marketing_gtm_id'));
        $this->assertEquals('987654321098765', SystemSetting::get('marketing_pixel_id'));
        $this->assertEquals('<script>console.log("analytics initialized");</script>', SystemSetting::get('marketing_custom_head'));
    }

    public function test_admin_can_simulate_serp_snippet_preview()
    {
        $meta = SeoMeta::create([
            'page_route' => '/locations/ca/los-angeles',
            'meta_title' => 'Los Angeles Adult Dating & Personals',
            'meta_description' => 'Find verified adults in Los Angeles with discreet privacy.',
            'robots_directive' => 'index, follow',
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class, ['initialTab' => 'seo'])
            ->call('selectSerpPreview', $meta->id)
            ->assertSet('seoSubTab', 'serp')
            ->assertSet('serpPreviewRoute', '/locations/ca/los-angeles')
            ->assertSet('serpPreviewTitle', 'Los Angeles Adult Dating & Personals')
            ->assertSee('Los Angeles Adult Dating &amp; Personals', false)
            ->set('serpDevice', 'mobile')
            ->assertSet('serpDevice', 'mobile');
    }

    public function test_admin_can_view_organic_search_trends()
    {
        $state = State::create([
            'country_id' => $this->country->id,
            'name' => 'Texas',
            'slug' => 'texas',
            'code' => 'TX',
            'is_active' => true,
        ]);

        $city = City::create([
            'state_id' => $state->id,
            'name' => 'Austin',
            'slug' => 'austin',
            'latitude' => 30.2672,
            'longitude' => -97.7431,
        ]);

        SearchTrend::create([
            'query' => 'casual dating austin',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'results_count' => 42,
            'hits' => 128,
            'last_searched_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class, ['initialTab' => 'seo'])
            ->call('setSeoSubTab', 'trends')
            ->assertSet('seoSubTab', 'trends')
            ->assertSee('casual dating austin')
            ->assertSee('Austin, TX')
            ->assertSee('128 hits')
            ->assertSee('42');
    }
}
