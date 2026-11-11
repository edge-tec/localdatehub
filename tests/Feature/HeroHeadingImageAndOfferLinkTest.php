<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Admin\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HeroHeadingImageAndOfferLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_homepage_renders_default_hero_image_without_offer_link_by_default(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('/images/hero-model.jpg', false);
        $response->assertSee('alt="LocalDateHub Dating Lifestyle"', false);
        $response->assertDontSee('rel="noopener noreferrer sponsored"', false);
    }

    public function test_admin_can_upload_hero_heading_image_and_set_external_offer_link(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $image = UploadedFile::fake()->image('custom-hero.jpg', 1200, 900);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class)
            ->set('brandHeroImageUpload', $image)
            ->set('settingValues.hero_offer_link', 'https://partner.example.com/offer')
            ->set('settingValues.hero_offer_new_tab', 'true')
            ->set('settingValues.hero_offer_alt', 'VIP Dating Offer')
            ->call('saveSystemSettings')
            ->assertHasNoErrors();

        $heroUrl = SystemSetting::where('key', 'hero_image_url')->value('value');
        $this->assertNotNull($heroUrl);
        $this->assertStringStartsWith('/storage/branding/', $heroUrl);
        $this->assertStringEndsWith('.webp', $heroUrl);
        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $heroUrl), '/'));

        $this->assertSame('https://partner.example.com/offer', SystemSetting::where('key', 'hero_offer_link')->value('value'));
        $this->assertSame('true', SystemSetting::where('key', 'hero_offer_new_tab')->value('value'));
        $this->assertSame('VIP Dating Offer', SystemSetting::where('key', 'hero_offer_alt')->value('value'));

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee($heroUrl, false);
        $response->assertSee('href="https://partner.example.com/offer"', false);
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer sponsored"', false);
        $response->assertSee('alt="VIP Dating Offer"', false);
    }

    public function test_relative_offer_link_with_same_tab_omits_target_blank(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class)
            ->set('settingValues.hero_offer_link', '/register')
            ->set('settingValues.hero_offer_new_tab', 'false')
            ->call('saveSystemSettings')
            ->assertHasNoErrors();

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('href="/register"', false);
        $response->assertDontSee('rel="noopener noreferrer sponsored"', false);
    }

    public function test_unsafe_javascript_offer_link_is_rejected(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class)
            ->set('settingValues.hero_offer_link', 'javascript:alert(1)')
            ->call('saveSystemSettings')
            ->assertHasErrors(['settingValues.hero_offer_link']);
    }

    public function test_admin_can_clear_offer_link_and_reset_hero_image_to_default(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $image = UploadedFile::fake()->image('promo-hero.png', 1000, 800);

        $component = Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class)
            ->set('brandHeroImageUpload', $image)
            ->set('settingValues.hero_offer_link', 'https://partner.example.com/vip')
            ->call('saveSystemSettings')
            ->assertHasNoErrors();

        $component->call('clearHeroOfferLink')
            ->call('resetBrandAsset', 'hero_image_url')
            ->assertHasNoErrors();

        $this->assertSame('', SystemSetting::where('key', 'hero_offer_link')->value('value'));
        $this->assertSame('/images/hero-model.jpg', SystemSetting::where('key', 'hero_image_url')->value('value'));

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('/images/hero-model.jpg', false);
        $response->assertDontSee('https://partner.example.com/vip', false);
        $response->assertDontSee('rel="noopener noreferrer sponsored"', false);
    }

    public function test_non_admin_user_cannot_modify_hero_heading_or_offer_settings(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $image = UploadedFile::fake()->image('unauthorized.jpg', 800, 600);

        Livewire::actingAs($regularUser)
            ->test(AdminEnterpriseHub::class)
            ->assertForbidden();

        // Calling methods directly without admin permission is also blocked
        $this->actingAs($regularUser);
        $component = new AdminEnterpriseHub();

        foreach (['clearHeroOfferLink', 'saveSystemSettings'] as $method) {
            try {
                $component->{$method}();
                $this->fail("Expected 403 for {$method}");
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }

        try {
            $component->brandHeroImageUpload = $image;
            $component->updatedBrandHeroImageUpload();
            $this->fail('Expected 403 for updatedBrandHeroImageUpload');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
