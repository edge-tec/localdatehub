<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use App\Modules\Monetization\Models\PostingSetting;
use App\Services\CampaignAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class FolderCampaignAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->tempRoot = sys_get_temp_dir() . '/ldh_campaign_test_' . uniqid();
        File::ensureDirectoryExists($this->tempRoot . '/Maisy Lowe');
        File::ensureDirectoryExists($this->tempRoot . '/Other Profile');

        for ($i = 1; $i <= 5; $i++) {
            $this->createTestJpeg($this->tempRoot . "/Maisy Lowe/maisy_{$i}.jpg", 200 + $i, 250 + $i);
            $this->createTestJpeg($this->tempRoot . "/Other Profile/other_{$i}.jpg", 200 + $i, 250 + $i);
        }

        Category::create([
            'name' => 'Women Seeking Men',
            'slug' => 'women-seeking-men',
            'is_active' => true,
        ]);

        $country = Country::create([
            'name' => 'United States',
            'code' => 'US',
            'slug' => 'united-states',
            'is_active' => true,
        ]);

        $state = State::create([
            'country_id' => $country->id,
            'name' => 'Texas',
            'code' => 'TX',
            'slug' => 'texas',
            'is_active' => true,
        ]);

        City::create([
            'state_id' => $state->id,
            'name' => 'Austin',
            'slug' => 'austin',
            'latitude' => 30.2672,
            'longitude' => -97.7431,
            'is_active' => true,
        ]);

        City::create([
            'state_id' => $state->id,
            'name' => 'Dallas',
            'slug' => 'dallas',
            'latitude' => 32.7767,
            'longitude' => -96.7970,
            'is_active' => true,
        ]);

        City::create([
            'state_id' => $state->id,
            'name' => 'Houston',
            'slug' => 'houston',
            'latitude' => 29.7604,
            'longitude' => -95.3698,
            'is_active' => true,
        ]);

        City::create([
            'state_id' => $state->id,
            'name' => 'Inactive Town',
            'slug' => 'inactive-town',
            'latitude' => 31.0,
            'longitude' => -98.0,
            'is_active' => false,
        ]);

        City::create([
            'state_id' => $state->id,
            'name' => 'San Antonio',
            'slug' => 'san-antonio',
            'latitude' => 29.4241,
            'longitude' => -98.4936,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempRoot);
        parent::tearDown();
    }

    protected function createTestJpeg(string $path, int $width, int $height): void
    {
        $img = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($img, 240, 120, 160);
        imagefill($img, 0, 0, $bg);
        imagejpeg($img, $path, 90);
        imagedestroy($img);
    }

    public function test_it_discovers_single_folder_isolates_images_creates_profile_and_respects_quota(): void
    {
        // Default posting settings: free quota = 3, daily limit = 5, mode = quota_then_paid
        // With 4 active cities and 0 wallet credits, 3 should succeed and the 4th should fail due to insufficient credits (quota respected!)
        PostingSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'global_posting_mode' => 'quota_then_paid',
                'default_post_price_credits' => 10,
                'default_free_quota' => 3,
                'unlimited_posting_enabled' => false,
                'daily_limit' => 5,
                'weekly_limit' => 20,
                'monthly_limit' => 50,
                'approval_mode' => 'conditional',
                'new_account_manual_review' => true,
                'verified_user_auto_approve' => true,
            ]
        );

        /** @var CampaignAutomationService $service */
        $service = app(CampaignAutomationService::class);
        $report = $service->runCampaign(
            $this->tempRoot . '/Maisy Lowe',
            'manmarzi001@gmail.com',
            ['report_dir' => $this->tempRoot . '/reports']
        );

        $this->assertSame(1, $report['total_folders_discovered']);
        $this->assertSame(1, $report['total_profiles_created']);
        $this->assertSame(1, $report['total_states_processed']);
        $this->assertSame(4, $report['total_cities_eligible']);
        $this->assertCount(1, $report['unavailable_cities']);
        $this->assertSame('Inactive Town', $report['unavailable_cities'][0]['city_name']);

        // 3 free posts succeeded (pending_review because new_account_manual_review=true), 4th stopped on credit/quota limit
        $this->assertSame(3, $report['total_ads_pending_review']);
        $this->assertSame(0, $report['total_ads_published']);
        $this->assertCount(3, $report['published_listings']);
        $this->assertCount(1, $report['failed_advertisements']);

        // 1-image, 2-image, 3-image breakdown all represented
        $this->assertSame(1, $report['image_count_breakdown'][1]);
        $this->assertSame(1, $report['image_count_breakdown'][2]);
        $this->assertSame(1, $report['image_count_breakdown'][3]);
        $this->assertSame(6, $report['total_images_uploaded']);

        // Strictly isolated to Maisy Lowe images (no other_*.jpg)
        $listings = Listing::with('media')->get();
        $this->assertCount(3, $listings);
        $titles = $listings->pluck('title')->all();
        $this->assertCount(3, array_unique($titles));

        foreach ($listings as $listing) {
            foreach ($listing->media as $media) {
                $this->assertStringStartsWith('maisy_', $media->original_filename);
            }
        }

        // Report files exist
        $this->assertFileExists($report['report_files']['json']);
        $this->assertFileExists($report['report_files']['markdown']);
    }

    public function test_it_creates_separate_profile_with_aliased_account_email_when_contact_email_is_shared(): void
    {
        /** @var CampaignAutomationService $service */
        $service = app(CampaignAutomationService::class);
        $first = $service->resolveAdvertiserProfile('Maisy Lowe', 'manmarzi001@gmail.com');
        $second = $service->resolveAdvertiserProfile('Joanna Nash', 'manmarzi001@gmail.com');
        $reused = $service->resolveAdvertiserProfile('Joanna Nash', 'manmarzi001@gmail.com');

        $this->assertTrue($first['created']);
        $this->assertTrue($second['created']);
        $this->assertFalse($reused['created']);
        $this->assertNotSame($first['user']->id, $second['user']->id);
        $this->assertSame($second['user']->id, $reused['user']->id);
        $this->assertSame('manmarzi001@gmail.com', $first['user']->email);
        $this->assertSame('manmarzi001+joanna-nash@gmail.com', $second['user']->email);
    }

    public function test_dry_run_command_previews_without_writing_database_records(): void
    {
        $this->artisan('app:publish-folder-campaign', [
            'path' => $this->tempRoot . '/Maisy Lowe',
            '--email' => 'manmarzi001@gmail.com',
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame(0, User::where('email', 'manmarzi001@gmail.com')->count());
        $this->assertSame(0, Listing::count());
    }

    public function test_admin_can_save_user_posting_override_without_optional_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);
        $target = User::factory()->create([
            'name' => 'Maisy Lowe',
            'email' => 'manmarzi001@gmail.com',
            'account_status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.monetization.overrides.store'), [
            'user_id' => $target->id,
            'free_post_allowance' => null,
            'custom_price_credits' => 0,
            'approval_mode' => 'auto',
            'is_unlimited' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_posting_overrides', [
            'user_id' => $target->id,
            'is_unlimited' => 1,
            'custom_price_credits' => 0,
            'approval_mode' => 'auto',
        ]);
    }

    public function test_admin_can_save_global_monetization_settings_without_weekly_limit(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.monetization.settings.update'), [
            'global_posting_mode' => 'free',
            'default_post_price_credits' => 10,
            'default_free_quota' => 3,
            'daily_limit' => 5,
            'monthly_limit' => 50,
            'approval_mode' => 'auto',
            'verified_user_ranking_boost' => 25,
            'unlimited_posting_enabled' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posting_settings', [
            'global_posting_mode' => 'free',
            'approval_mode' => 'auto',
            'unlimited_posting_enabled' => 1,
        ]);
    }
}
