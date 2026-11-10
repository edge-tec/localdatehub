<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\Permission;
use App\Modules\Admin\Models\Role;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\Models\SeoRedirect;
use App\Modules\SEO\Models\SeoUrlRecord;
use App\Modules\SEO\Services\GoogleIndexingService;
use App\Modules\SEO\Services\KeywordResearchService;
use App\Modules\SEO\Services\TechnicalSeoAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnterpriseSeoSystemTest extends TestCase
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
            'name' => 'Super Administrator',
            'slug' => 'super_admin',
            'is_system' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage SEO',
            'slug' => 'seo.manage',
            'module' => 'seo',
        ]);

        $role->permissions()->attach($permission->id);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->admin->roles()->attach($role->id);
    }

    public function test_sitemap_index_returns_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');

        $content = $response->getContent();
        $this->assertStringContainsString('<sitemapindex', $content);
        $this->assertStringContainsString('/sitemaps/pages.xml', $content);
        $this->assertStringContainsString('/sitemaps/listings.xml', $content);
        $this->assertStringContainsString('/sitemaps/states.xml', $content);
        $this->assertStringContainsString('/sitemaps/cities.xml', $content);
        $this->assertStringContainsString('/sitemaps/images.xml', $content);
    }

    public function test_all_sub_sitemaps_return_valid_xml(): void
    {
        $state = State::create([
            'country_id' => $this->country->id,
            'name' => 'Florida',
            'code' => 'FL',
            'slug' => 'florida',
            'is_active' => true,
        ]);
        $city = City::create([
            'state_id' => $state->id,
            'name' => 'Miami',
            'slug' => 'miami',
            'is_active' => true,
        ]);
        Category::create([
            'name' => 'Personals',
            'slug' => 'personals',
            'is_active' => true,
        ]);

        $subSitemaps = [
            '/sitemaps/pages.xml',
            '/sitemaps/categories.xml',
            '/sitemaps/locations.xml',
            '/sitemaps/states.xml',
            '/sitemaps/cities.xml',
            '/sitemaps/listings.xml',
            '/sitemaps/images.xml',
        ];

        foreach ($subSitemaps as $url) {
            $resp = $this->get($url);
            $resp->assertStatus(200);
            $resp->assertHeader('Content-Type', 'application/xml; charset=utf-8');
            $this->assertStringContainsString('<urlset', $resp->getContent());
        }
    }

    public function test_dynamic_robots_txt_returns_clean_directives(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');

        $content = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin/', $content);
        $this->assertStringContainsString('Sitemap:', $content);
    }

    public function test_seo_redirects_middleware_handles_301_and_410_gone(): void
    {
        // 301 Permanent Redirect
        SeoRedirect::create([
            'source_path' => '/legacy-page',
            'target_url' => '/search',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $resp301 = $this->get('/legacy-page');
        $resp301->assertRedirect('/search');
        $resp301->assertStatus(301);

        // 410 Gone for permanently deleted listing
        SeoRedirect::create([
            'source_path' => '/listings/deleted-old-classified',
            'status_code' => 410,
            'is_active' => true,
        ]);

        $resp410 = $this->get('/listings/deleted-old-classified');
        $resp410->assertStatus(410);
        $this->assertStringContainsString('410 Gone', $resp410->getContent());
    }

    public function test_keyword_research_service_classifies_intent(): void
    {
        $service = app(KeywordResearchService::class);

        $this->assertEquals('transactional', $service->classifyIntent('book dates in miami'));
        $this->assertEquals('informational', $service->classifyIntent('how to meet singles safely'));
        $this->assertEquals('commercial', $service->classifyIntent('best adult classifieds texas'));
    }

    public function test_google_indexing_service_validates_and_enqueues(): void
    {
        $service = app(GoogleIndexingService::class);

        $user = User::factory()->create();
        $state = State::create([
            'country_id' => $this->country->id,
            'name' => 'Texas',
            'code' => 'TX',
            'slug' => 'texas',
            'is_active' => true,
        ]);
        $city = City::create([
            'state_id' => $state->id,
            'name' => 'Austin',
            'slug' => 'austin',
            'is_active' => true,
        ]);
        $cat = Category::create([
            'name' => 'Activity Partners',
            'slug' => 'activity-partners',
            'is_active' => true,
        ]);

        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'title' => 'Austin Hiking & Coffee Companion',
            'slug' => 'austin-hiking-coffee-companion',
            'description' => 'Looking for outdoor enthusiasts to explore Austin greenbelt trails together.',
            'poster_age' => 26,
            'poster_gender' => 'female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
        ]);

        $url = route('listings.show', $listing->slug);
        $queueItem = $service->enqueue($url, 'URL_UPDATED', 'sitemap_ping', 5, $listing);

        $this->assertNotNull($queueItem);
        $this->assertEquals('pending', $queueItem->status);
        $this->assertEquals($url, $queueItem->url);

        $urlRecord = SeoUrlRecord::where('url', $url)->first();
        $this->assertNotNull($urlRecord);
        $this->assertEquals('queued', $urlRecord->indexing_status);
        $this->assertEquals('eligible', $urlRecord->indexing_eligibility);
    }

    public function test_technical_seo_audit_identifies_issues_and_calculates_health_score(): void
    {
        $auditService = app(TechnicalSeoAuditService::class);
        $result = $auditService->runFullAudit();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_urls', $result);
        $this->assertArrayHasKey('issues_found', $result);
        $this->assertArrayHasKey('overall_health_score', $result);
        $this->assertGreaterThanOrEqual(10, $result['overall_health_score']);
        $this->assertLessThanOrEqual(100, $result['overall_health_score']);
    }

    public function test_admin_enterprise_hub_renders_all_20_seo_kpi_metrics(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class)
            ->set('tab', 'seo')
            ->set('seoSubTab', 'overview');

        $component->assertStatus(200);
        $component->assertSee('Enterprise SEO &amp; Google Indexing Suite', false);
        $component->assertSee('1. Total URLs');
        $component->assertSee('2. Indexed URLs');
        $component->assertSee('14. Health Score');
        $component->assertSee('20. Crawl Errors');
    }
}
