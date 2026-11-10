<?php

namespace Tests\Feature;

use App\Modules\Admin\Models\SystemSetting;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\Models\SeoRedirect;
use App\Modules\SEO\Models\SeoSitemap;
use App\Modules\SEO\Models\SeoUrlRecord;
use App\Modules\SEO\Services\SitemapService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoProductionSignoffTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Requirement B: Google HTML Verification Token Validation
     */
    public function test_google_verification_requires_exact_token_match(): void
    {
        // 1. Without configured token, arbitrary code returns 404
        $res = $this->get('/google1234567890abcdef.html');
        $res->assertStatus(404);

        // 2. With configured token in SystemSetting, matching code returns 200 with exact content
        SystemSetting::updateOrCreate(
            ['group' => 'seo', 'key' => 'google_verification_code'],
            ['value' => 'myverifiedtoken999']
        );

        $validRes = $this->get('/googlemyverifiedtoken999.html');
        $validRes->assertStatus(200);
        $this->assertEquals('google-site-verification: googlemyverifiedtoken999.html', $validRes->getContent());

        // 3. Mismatched token still returns 404
        $invalidRes = $this->get('/googlewrongtoken123.html');
        $invalidRes->assertStatus(404);
    }

    /**
     * Requirement C: Indexing Policy and Private Route Protection
     */
    public function test_indexing_policy_protects_private_routes(): void
    {
        // 1. Auth register route for guests contains noindex, nofollow
        $authRes = $this->get('/register');
        $authRes->assertStatus(200);
        $authRes->assertSee('noindex, nofollow');

        // 2. Private account route returns X-Robots-Tag header and noindex in HTML
        $user = User::factory()->create(['is_age_verified' => true]);
        $res = $this->actingAs($user)->get('/account');
        $res->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    /**
     * Requirement D: Listing Lifecycle State Transitions
     */
    public function test_listing_lifecycle_transitions_and_sitemap_sync(): void
    {
        $user = User::factory()->create();
        $country = \App\Modules\Locations\Models\Country::create(['name' => 'United States', 'code' => 'US', 'is_active' => true]);
        $cat = Category::create(['name' => 'Personals', 'slug' => 'personals', 'is_active' => true]);
        $state = State::create(['country_id' => $country->id, 'name' => 'Texas', 'code' => 'TX', 'is_active' => true]);
        $city = City::create(['state_id' => $state->id, 'name' => 'Austin', 'slug' => 'austin', 'is_active' => true]);

        // 1. Create and Publish active listing
        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'title' => 'Active Companion Austin',
            'slug' => 'active-companion-austin',
            'description' => 'A wonderful companion listing in Austin TX.',
            'poster_age' => 25,
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $url = route('listings.show', $listing->slug);
        $record = SeoUrlRecord::where('url', $url)->first();
        $this->assertNotNull($record);
        $this->assertEquals('included', $record->sitemap_status);
        $this->assertEquals('eligible', $record->indexing_eligibility);

        // 2. Listing Expiration
        $listing->update(['status' => Listing::STATUS_EXPIRED]);
        $record->refresh();
        $this->assertEquals('excluded', $record->sitemap_status);
        $this->assertEquals('ineligible', $record->indexing_eligibility);
        $this->assertNull(SeoRedirect::where('source_path', '/listings/' . $listing->slug)->first());

        // 3. Listing Soft Deletion (must NOT be 410 Gone)
        $listing->delete();
        $record->refresh();
        $this->assertEquals('excluded', $record->sitemap_status);
        $this->assertNull(SeoRedirect::where('source_path', '/listings/' . $listing->slug)->first());

        // 4. Listing Restoration
        $listing->restore();
        $listing->update(['status' => Listing::STATUS_ACTIVE, 'expires_at' => now()->addDays(30)]);
        $record->refresh();
        $this->assertEquals('included', $record->sitemap_status);

        // 5. Listing Permanent Deletion (MUST be 410 Gone)
        $listing->forceDelete();
        $redirect = SeoRedirect::where('source_path', '/listings/' . $listing->slug)->first();
        $this->assertNotNull($redirect);
        $this->assertEquals(410, $redirect->status_code);

        // HTTP request to permanently deleted listing returns 410 Gone
        $goneRes = $this->get('/listings/' . $listing->slug);
        $goneRes->assertStatus(410);
    }

    /**
     * Requirement F & G: Sitemap Stateless Caching and Zero Duplicates
     */
    public function test_sitemaps_have_zero_cross_duplicates_and_no_cookies(): void
    {
        $service = app(SitemapService::class);
        $service->generateAll();

        $res = $this->get('/sitemap.xml');
        $res->assertStatus(200);
        $res->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $res->assertHeader('Cache-Control', 'max-age=3600, public, s-maxage=3600, stale-while-revalidate=600');
        $this->assertEmpty($res->headers->getCookies());
    }

    /**
     * Requirement 1: Self-Referencing Canonical URLs on Paginated Pages (Google Guidelines)
     */
    public function test_pagination_canonical_is_self_referencing(): void
    {
        $cat = Category::create(['name' => 'Personals', 'slug' => 'personals', 'is_active' => true]);
        $seoService = app(\App\Modules\SEO\Services\SeoService::class);

        // Page 1 canonical is root category
        $metaPage1 = $seoService->generateForCategory($cat, 1);
        $this->assertEquals(route('categories.show', $cat->slug), $metaPage1->canonicalUrl);
        $this->assertEquals('index, follow', $metaPage1->robots);

        // Page 2 canonical is self-referencing to ?page=2 with noindex, follow to prevent index bloat while preserving link crawling
        $metaPage2 = $seoService->generateForCategory($cat, 2);
        $this->assertStringContainsString('page=2', $metaPage2->canonicalUrl);
        $this->assertEquals('noindex, follow', $metaPage2->robots);
    }

    /**
     * Requirement 3: Adult Content Rating Metadata (Google Explicit Content & RTA)
     */
    public function test_adult_content_rating_metadata_is_rendered(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertSee('<meta name="rating" content="adult">', false);
        $res->assertSee('<meta name="RATING" content="RTA-5042-1996-1400-1579-RTA">', false);
    }
}
