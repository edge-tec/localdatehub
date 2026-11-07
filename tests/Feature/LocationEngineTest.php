<?php

namespace Tests\Feature;

use App\Livewire\DependentLocationSelector;
use App\Livewire\LocationAutocomplete;
use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Jobs\ImportLocationsJob;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\LocationAlias;
use App\Modules\Locations\Models\LocationImportHistory;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use App\Modules\Locations\Services\LocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class LocationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected LocationService $locationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locationService = app(LocationService::class);

        // Run full location import to load verified dataset
        $this->artisan('locations:import')->assertSuccessful();
    }

    /**
     * Test verified location import command processes all entities and records history.
     */
    public function test_location_import_command_imports_full_dataset(): void
    {
        $this->assertDatabaseHas('countries', ['code' => 'US']);
        $this->assertGreaterThanOrEqual(56, State::count());
        $this->assertGreaterThanOrEqual(80, City::count());
        $this->assertGreaterThanOrEqual(40, ZipCode::count());
        $this->assertGreaterThanOrEqual(20, LocationAlias::count());

        // Verify version history was logged
        $history = LocationImportHistory::latest()->first();
        $this->assertNotNull($history);
        $this->assertEquals('completed', $history->status);
        $this->assertGreaterThan(0, $history->records_processed);
        $this->assertNotEmpty($history->checksum);
    }

    /**
     * Test import command with --queue flag dispatches background job.
     */
    public function test_import_command_with_queue_flag_dispatches_job(): void
    {
        Queue::fake();

        $this->artisan('locations:import --queue')->assertSuccessful();

        Queue::assertPushed(ImportLocationsJob::class);
    }

    /**
     * Test integrity audit command passes all diagnostic checks.
     */
    public function test_location_validate_command_passes_integrity_checks(): void
    {
        $this->artisan('locations:validate')->assertSuccessful();
    }

    /**
     * Test all 50 states + DC + Territories are present and filterable.
     */
    public function test_states_and_territories_representation(): void
    {
        // 50 states + DC
        $statesOnly = State::where('is_active', true)->statesOnly()->get();
        $this->assertCount(51, $statesOnly);

        // 5 US territories (PR, GU, VI, AS, MP)
        $territoriesOnly = State::where('is_active', true)->territoriesOnly()->get();
        $this->assertCount(5, $territoriesOnly);

        $codes = $territoriesOnly->pluck('code')->toArray();
        $this->assertContains('PR', $codes);
        $this->assertContains('GU', $codes);
        $this->assertContains('VI', $codes);
        $this->assertContains('AS', $codes);
        $this->assertContains('MP', $codes);
    }

    /**
     * Test ZIP code lookup supports multiple locality relationships.
     */
    public function test_zip_code_lookup_supports_multiple_localities(): void
    {
        // 75024 maps to Plano and Dallas in our verified dataset
        $localities = $this->locationService->lookupZipCode('75024');

        $this->assertGreaterThanOrEqual(2, $localities->count());
        $cities = $localities->pluck('city_slug')->toArray();
        $this->assertContains('plano', $cities);
        $this->assertContains('dallas', $cities);
    }

    /**
     * Test location autocomplete across city, ZIP, state, and aliases.
     */
    public function test_location_autocomplete_multi_tier_matching(): void
    {
        // 1. City match
        $results = $this->locationService->searchAutocomplete('Austin');
        $this->assertNotEmpty($results);
        $this->assertEquals('city', $results->first()['type']);
        $this->assertStringContainsString('Austin', $results->first()['title']);

        // 2. ZIP match
        $zipResults = $this->locationService->searchAutocomplete('78701');
        $this->assertNotEmpty($zipResults);
        $this->assertEquals('zip', $zipResults->first()['type']);
        $this->assertStringContainsString('78701', $zipResults->first()['title']);

        // 3. Alias / Metro match (e.g. "Silicon Valley", "DFW", "Twin Cities")
        $aliasResults = $this->locationService->searchAutocomplete('Silicon Valley');
        $this->assertNotEmpty($aliasResults);
        $this->assertEquals('alias', $aliasResults->first()['type']);
        $this->assertStringContainsString('Silicon Valley', $aliasResults->first()['title']);

        // 4. State match
        $stateResults = $this->locationService->searchAutocomplete('TX');
        $this->assertNotEmpty($stateResults);
        $types = $stateResults->pluck('type')->toArray();
        $this->assertContains('state', $types);
    }

    /**
     * Test location normalization helper.
     */
    public function test_location_normalization_expands_abbreviations(): void
    {
        $this->assertEquals('New York City', $this->locationService->normalizeLocationQuery('NYC'));
        $this->assertEquals('Los Angeles', $this->locationService->normalizeLocationQuery('LA'));
        $this->assertEquals('Saint Louis', $this->locationService->normalizeLocationQuery('St. Louis'));
        $this->assertEquals('Fort Worth', $this->locationService->normalizeLocationQuery('Ft. Worth'));
    }

    /**
     * Test Haversine radius geographic search.
     */
    public function test_haversine_radius_search_finds_nearby_cities(): void
    {
        // Austin coordinates: (30.2672, -97.7431)
        // San Antonio is ~74 miles south of Austin
        // Dallas is ~182 miles north of Austin
        $nearbyCities = $this->locationService->findNearbyCities(30.2672, -97.7431, 85.0);

        $cityNames = $nearbyCities->pluck('name')->toArray();
        $this->assertContains('San Antonio', $cityNames);
        $this->assertNotContains('Dallas', $cityNames); // Beyond 85 miles
        $this->assertNotContains('Seattle', $cityNames);
    }

    /**
     * Test dependent location selector Livewire component.
     */
    public function test_dependent_location_selector_livewire_component(): void
    {
        // 1. Initial render without state
        $test = Livewire::test(DependentLocationSelector::class)
            ->assertSee('Select State or Territory');

        // 2. Select state 'TX' -> loads Texas cities
        $test->set('selectedState', 'TX')
            ->assertSee('Austin')
            ->assertSee('Dallas')
            ->assertSee('Houston');

        // 3. Enter 5-digit ZIP '90012' -> auto-resolves to California & Los Angeles
        $test->set('zipCode', '90012')
            ->assertSet('selectedState', 'CA');

        $city = City::find($test->get('selectedCityId'));
        $this->assertNotNull($city);
        $this->assertEquals('Los Angeles', $city->name);
    }

    /**
     * Test public API endpoints.
     */
    public function test_public_location_api_endpoints(): void
    {
        // States list
        $response = $this->getJson('/api/locations/states');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['count', 'states']]);

        // State cities
        $response = $this->getJson('/api/locations/states/TX/cities');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.state.code', 'TX');

        // ZIP lookup
        $response = $this->getJson('/api/locations/zip/78701');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.zip_code', '78701');

        // Autocomplete
        $response = $this->getJson('/api/locations/autocomplete?q=Austin');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['query', 'count', 'results']]);

        // Nearby
        $response = $this->getJson('/api/locations/nearby?lat=30.2672&lng=-97.7431&radius=80');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['center', 'radius_miles', 'cities']]);
    }

    /**
     * Test web directory and landing pages including category SEO URL.
     */
    public function test_web_location_landing_pages(): void
    {
        // National Overview
        $response = $this->get('/locations');
        $response->assertStatus(200)
            ->assertSee('United States Personal Classifieds Directory')
            ->assertSee('Texas');

        // State Landing Page
        $response = $this->get('/locations/TX');
        $response->assertStatus(200)
            ->assertSee('Texas Adult Personal Classifieds')
            ->assertSee('Austin');

        // City Landing Page
        $response = $this->get('/locations/TX/austin');
        $response->assertStatus(200)
            ->assertSee('Austin, TX')
            ->assertSee('Nearby Cities');

        // Category-specific SEO landing page (/locations/TX/austin/women-seeking-men)
        $category = Category::firstOrCreate(
            ['slug' => 'women-seeking-men'],
            ['name' => 'Women Seeking Men', 'code' => 'W4M', 'is_active' => true]
        );

        $response = $this->get("/locations/TX/austin/{$category->slug}");
        $response->assertStatus(200)
            ->assertSee('Austin, TX');

        // Invalid State returns 404
        $response = $this->get('/locations/ZZ');
        $response->assertStatus(404);
    }

    /**
     * Test administrative location management authorization, operations, and history.
     */
    public function test_admin_location_management_workflow(): void
    {
        $regularUser = User::factory()->create(['role' => 'user']);
        $adminUser = User::factory()->create(['role' => 'admin']);

        // Non-admin receives 403
        $this->actingAs($regularUser)->get('/admin/locations')->assertStatus(403);
        $this->actingAs($regularUser)->get('/admin/locations/history')->assertStatus(403);

        // Admin can view dashboard
        $response = $this->actingAs($adminUser)->get('/admin/locations');
        $response->assertStatus(200)
            ->assertSee('Manage Cities');

        // Admin can view import history
        $response = $this->actingAs($adminUser)->get('/admin/locations/history');
        $response->assertStatus(200)
            ->assertSee('Dataset Import Version History');

        // Admin can update city
        $city = City::where('slug', 'austin')->firstOrFail();
        $response = $this->actingAs($adminUser)->put("/admin/locations/cities/{$city->id}", [
            'name' => 'Austin Metro',
            'latitude' => 30.2672,
            'longitude' => -97.7431,
            'population' => 980000,
            'timezone' => 'America/Chicago',
            'is_popular' => '1',
            'is_active' => '1',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertEquals('Austin Metro', $city->fresh()->name);

        // Admin can add alias
        $response = $this->actingAs($adminUser)->post("/admin/locations/cities/{$city->id}/aliases", [
            'alias' => 'Bat City',
            'alias_type' => 'colloquial',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('location_aliases', [
            'city_id' => $city->id,
            'alias' => 'Bat City',
        ]);

        // Admin can delete alias
        $alias = LocationAlias::where('alias', 'Bat City')->firstOrFail();
        $response = $this->actingAs($adminUser)->delete("/admin/locations/aliases/{$alias->id}");
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('location_aliases', ['id' => $alias->id]);
    }
}
