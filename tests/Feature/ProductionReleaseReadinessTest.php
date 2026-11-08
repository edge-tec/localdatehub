<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductionReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected State $state;
    protected City $city;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $country = Country::firstOrCreate(['code' => 'US'], ['name' => 'United States', 'is_active' => true]);

        $this->state = State::create([
            'country_id' => $country->id,
            'name' => 'Nevada',
            'code' => 'NV',
            'slug' => 'nevada',
            'is_active' => true,
        ]);

        $this->city = City::create([
            'state_id' => $this->state->id,
            'name' => 'Las Vegas',
            'slug' => 'las-vegas',
            'is_active' => true,
            'population' => 650000,
            'is_major' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Women Seeking Men',
            'slug' => 'women-seeking-men',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_production_deployment_configuration_files_and_scripts_exist_and_are_executable(): void
    {
        $requiredFiles = [
            base_path('deployment/nginx/localadzone.conf'),
            base_path('deployment/php/localadzone-php-fpm.conf'),
            base_path('deployment/supervisor/localadzone-horizon.conf'),
            base_path('deployment/supervisor/localadzone-scheduler.conf'),
            base_path('bin/deploy.sh'),
            base_path('bin/rollback.sh'),
            base_path('bin/backup.sh'),
            base_path('bin/restore.sh'),
            base_path('bin/verify-staging.sh'),
            base_path('.env.production.example'),
        ];

        foreach ($requiredFiles as $file) {
            $this->assertFileExists($file, "Required production file {$file} is missing.");
        }

        // Verify shell scripts are executable
        $this->assertTrue(is_executable(base_path('bin/deploy.sh')));
        $this->assertTrue(is_executable(base_path('bin/rollback.sh')));
        $this->assertTrue(is_executable(base_path('bin/backup.sh')));
        $this->assertTrue(is_executable(base_path('bin/restore.sh')));
        $this->assertTrue(is_executable(base_path('bin/verify-staging.sh')));
    }

    public function test_mandatory_production_documentation_reports_exist(): void
    {
        $docs = [
            base_path('docs/PRODUCTION_DEPLOYMENT_GUIDE.md'),
            base_path('docs/ENVIRONMENT_VARIABLES.md'),
            base_path('docs/BACKUP_AND_RESTORE_GUIDE.md'),
            base_path('docs/INCIDENT_RESPONSE_PLAN.md'),
            base_path('docs/RELEASE_CHECKLIST.md'),
            base_path('docs/FINAL_PRODUCTION_AUDIT.md'),
        ];

        foreach ($docs as $doc) {
            $this->assertFileExists($doc, "Mandatory documentation {$doc} is missing.");
            $content = file_get_contents($doc);
            $this->assertNotEmpty($content);
        }
    }

    public function test_scheduled_maintenance_jobs_are_registered_in_console_routes(): void
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertStringContainsString('promotions:expire', $output);
        $this->assertStringContainsString('billing:process-renewals', $output);
        $this->assertStringContainsString('listings:notify-expiring', $output);
        $this->assertStringContainsString('billing:reconcile-transactions', $output);
    }

    public function test_public_routes_respond_with_http_200(): void
    {
        $publicRoutes = [
            route('home'),
            route('search.index'),
            route('categories.show', $this->category->slug),
            route('locations.state', ['stateCode' => strtolower($this->state->code)]),
            route('locations.city', ['stateCode' => strtolower($this->state->code), 'citySlug' => $this->city->slug]),
            route('pages.about'),
            route('pages.safety'),
            route('pages.terms'),
            route('pages.privacy'),
            route('pages.pricing'),
        ];

        foreach ($publicRoutes as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
        }
    }

    public function test_admin_routes_strictly_enforce_authentication_and_rbac(): void
    {
        // 1. Unauthenticated request redirects to login
        $response = $this->get('/admin');
        $response->assertRedirect('/login');

        // 2. Regular user receives 403 Forbidden
        $user = User::factory()->create(['role' => 'user']);
        $forbiddenResponse = $this->actingAs($user)->get('/admin');
        $forbiddenResponse->assertStatus(403);
    }

    public function test_sitemap_and_robots_txt_respond_with_proper_content_types(): void
    {
        $sitemapResp = $this->get('/sitemap.xml');
        $sitemapResp->assertStatus(200);
        $sitemapResp->assertHeader('Content-Type', 'application/xml; charset=utf-8');

        $robotsResp = $this->get('/robots.txt');
        $robotsResp->assertStatus(200);
        $robotsResp->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $robotsResp->assertSee('Sitemap:');
        $robotsResp->assertSee('User-agent: *');
    }

    public function test_maintenance_mode_activation_and_deactivation(): void
    {
        // Test activating maintenance mode
        Artisan::call('down');
        $this->assertTrue(app()->isDownForMaintenance());

        // Test bringing back up
        Artisan::call('up');
        $this->assertFalse(app()->isDownForMaintenance());
    }
}
