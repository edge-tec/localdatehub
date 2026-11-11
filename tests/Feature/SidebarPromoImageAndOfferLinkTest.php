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

class SidebarPromoImageAndOfferLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_homepage_renders_default_sidebar_promo_image_overlay_and_register_link_by_default(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('/images/promo-banner.jpg', false);
        $response->assertSee('alt="Find Your Perfect Match"', false);
        $response->assertSee('Find Your Perfect Match', false);
        $response->assertSee('text-xl font-bold font-serif italic text-pink-600 drop-shadow-md', false);
        $response->assertSee('href="' . route('register') . '"', false);
        $response->assertDontSee('rel="noopener noreferrer sponsored"', false);
    }

    public function test_admin_can_upload_sidebar_promo_image_and_set_external_offer_link(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $image = UploadedFile::fake()->image('sidebar-promo.png', 600, 600);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class)
            ->set('brandSidebarPromoUpload', $image)
            ->set('settingValues.sidebar_promo_offer_link', 'https://partner.example.com/sidebar-deal')
            ->set('settingValues.sidebar_promo_offer_new_tab', 'true')
            ->set('settingValues.sidebar_promo_offer_alt', 'Exclusive VIP Offer')
            ->call('saveSystemSettings')
            ->assertHasNoErrors();

        $sidebarUrl = SystemSetting::where('key', 'sidebar_promo_image_url')->value('value');
        $this->assertNotNull($sidebarUrl);
        $this->assertStringStartsWith('/storage/branding/', $sidebarUrl);
        $this->assertStringEndsWith('.webp', $sidebarUrl);
        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $sidebarUrl), '/'));

        $this->assertSame('https://partner.example.com/sidebar-deal', SystemSetting::where('key', 'sidebar_promo_offer_link')->value('value'));
        $this->assertSame('true', SystemSetting::where('key', 'sidebar_promo_offer_new_tab')->value('value'));
        $this->assertSame('Exclusive VIP Offer', SystemSetting::where('key', 'sidebar_promo_offer_alt')->value('value'));

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee($sidebarUrl, false);
        $response->assertSee('href="https://partner.example.com/sidebar-deal"', false);
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer sponsored"', false);
        $response->assertSee('alt="Exclusive VIP Offer"', false);
        $response->assertDontSee('text-xl font-bold font-serif italic text-pink-600 drop-shadow-md', false);
    }

    public function test_relative_sidebar_promo_offer_link_with_same_tab_omits_target_blank(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class)
            ->set('settingValues.sidebar_promo_offer_link', '/pricing')
            ->set('settingValues.sidebar_promo_offer_new_tab', 'false')
            ->call('saveSystemSettings')
            ->assertHasNoErrors();

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('href="/pricing"', false);
        $response->assertDontSee('rel="noopener noreferrer sponsored"', false);
    }

    public function test_unsafe_javascript_sidebar_promo_offer_link_is_rejected(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class)
            ->set('settingValues.sidebar_promo_offer_link', 'javascript:alert(1)')
            ->call('saveSystemSettings')
            ->assertHasErrors(['settingValues.sidebar_promo_offer_link']);
    }

    public function test_admin_can_clear_sidebar_promo_offer_link_and_reset_image_to_default(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $image = UploadedFile::fake()->image('custom-sidebar.png', 600, 600);

        $component = Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class)
            ->set('brandSidebarPromoUpload', $image)
            ->set('settingValues.sidebar_promo_offer_link', 'https://partner.example.com/sidebar-vip')
            ->call('saveSystemSettings')
            ->assertHasNoErrors();

        $component->call('clearSidebarPromoOfferLink')
            ->call('resetBrandAsset', 'sidebar_promo_image_url')
            ->assertHasNoErrors();

        $this->assertSame('', SystemSetting::where('key', 'sidebar_promo_offer_link')->value('value'));
        $this->assertSame('/images/promo-banner.jpg', SystemSetting::where('key', 'sidebar_promo_image_url')->value('value'));

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('/images/promo-banner.jpg', false);
        $response->assertSee('text-xl font-bold font-serif italic text-pink-600 drop-shadow-md', false);
        $response->assertSee('href="' . route('register') . '"', false);
        $response->assertDontSee('https://partner.example.com/sidebar-vip', false);
        $response->assertDontSee('rel="noopener noreferrer sponsored"', false);
    }

    public function test_non_admin_user_cannot_modify_sidebar_promo_or_offer_settings(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $image = UploadedFile::fake()->image('unauthorized.png', 600, 600);

        Livewire::actingAs($regularUser)
            ->test(AdminEnterpriseHub::class)
            ->assertForbidden();

        $this->actingAs($regularUser);
        $component = new AdminEnterpriseHub();

        foreach (['clearSidebarPromoOfferLink', 'saveSystemSettings'] as $method) {
            try {
                $component->{$method}();
                $this->fail("Expected 403 for {$method}");
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }

        try {
            $component->brandSidebarPromoUpload = $image;
            $component->updatedBrandSidebarPromoUpload();
            $this->fail('Expected 403 for updatedBrandSidebarPromoUpload');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
