<?php

namespace Tests\Feature;

use App\Livewire\AccountDashboard;
use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Advertising\Models\AdPlacementConfig;
use App\Modules\Advertising\Services\AdDeliveryService;
use App\Modules\Advertising\Services\AdTrackingService;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use App\Modules\Payments\Crypto\Models\CryptoCurrency;
use App\Modules\Payments\Crypto\Models\CryptoNetwork;
use App\Modules\Payments\Crypto\Services\InHouse\CryptoInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class SiteHealthAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_listings_search_and_store_work_cleanly_and_block_suspended_users(): void
    {
        $country = Country::create(['name' => 'United States', 'code' => 'US', 'is_active' => true]);
        $state = State::create(['country_id' => $country->id, 'name' => 'Texas', 'code' => 'TX', 'slug' => 'texas', 'is_active' => true]);
        $city = City::create(['state_id' => $state->id, 'name' => 'Austin', 'slug' => 'austin', 'is_active' => true]);
        $category = Category::create(['name' => 'Women Seeking Men', 'slug' => 'women-seeking-men', 'is_active' => true]);
        $user = User::factory()->create(['role' => 'user', 'account_status' => 'active', 'is_age_verified' => true]);

        Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'title' => 'Coffee & Live Music Partner Austin',
            'slug' => 'coffee-live-music-partner-austin',
            'description' => 'Looking to meet genuinely friendly locals for coffee in Austin.',
            'poster_age' => 27,
            'poster_gender' => 'woman',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // Search API must return 200 (previously threw Unknown named parameter $radiusMiles)
        $this->getJson('/api/listings/search?q=Coffee&state=TX&photos_only=0')
            ->assertOk()
            ->assertJsonPath('status', 200)
            ->assertJsonCount(1, 'data.listings');

        // Active user can create listing via API
        $this->actingAs($user)->postJson('/api/listings', [
            'title' => 'Weekend Trail Hike Austin',
            'description' => 'Friendly companion sought for weekend Barton Creek greenbelt hikes.',
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'poster_age' => 29,
        ])->assertStatus(201)->assertJsonPath('data.listing.poster_age', 29);

        // Suspended user is rejected with 403
        $user->update(['account_status' => 'suspended']);
        $this->actingAs($user)->postJson('/api/listings', [
            'title' => 'Blocked Post Attempt',
            'description' => 'This description has plenty of characters for validation.',
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
        ])->assertStatus(403);
    }

    public function test_super_admin_and_moderators_are_authorized_by_listing_policy(): void
    {
        $country = Country::create(['name' => 'United States', 'code' => 'US', 'is_active' => true]);
        $state = State::create(['country_id' => $country->id, 'name' => 'Texas', 'code' => 'TX', 'slug' => 'texas', 'is_active' => true]);
        $city = City::create(['state_id' => $state->id, 'name' => 'Austin', 'slug' => 'austin', 'is_active' => true]);
        $category = Category::create(['name' => 'Personals', 'slug' => 'personals', 'is_active' => true]);

        $owner = User::factory()->create(['role' => 'user', 'account_status' => 'active']);
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'account_status' => 'active']);

        $pendingListing = Listing::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'title' => 'Pending Moderation Listing',
            'slug' => 'pending-moderation-listing',
            'description' => 'Pending moderation listing awaiting staff review.',
            'poster_age' => 26,
            'status' => Listing::STATUS_PENDING_REVIEW,
        ]);

        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $pendingListing));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $pendingListing));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('delete', $pendingListing));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('moderate', $pendingListing));
    }

    public function test_suspend_user_modal_binds_to_user_suspend_reason_and_account_dashboard_nav_has_no_livewire_close(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'account_status' => 'active']);
        $target = User::factory()->create(['role' => 'user', 'account_status' => 'active', 'is_age_verified' => true]);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->call('openSuspendUserModal', $target->id)
            ->assertSeeHtml('wire:model="userSuspendReason"')
            ->assertSeeHtml('wire:model="userSuspendType"')
            ->assertDontSeeHtml('wire:model="suspensionReason"');

        Livewire::actingAs($target)
            ->test(AccountDashboard::class)
            ->assertDontSeeHtml("setTab('overview'); close()");
    }

    public function test_crypto_invoice_amount_range_exception_renders_clean_dollar_amounts(): void
    {
        $currency = CryptoCurrency::create([
            'symbol' => 'USDT',
            'name' => 'Tether USD',
            'is_active' => true,
            'min_deposit_usd' => 10.00,
            'max_deposit_usd' => 5000.00,
        ]);
        CryptoNetwork::create([
            'currency_id' => $currency->id,
            'network_key' => 'TRC20',
            'chain_name' => 'TRON TRC-20',
            'gas_currency' => 'TRX',
            'rpc_endpoint_primary' => 'https://api.trongrid.io',
            'is_active' => true,
            'required_confirmations' => 1,
        ]);

        $user = User::factory()->create(['account_status' => 'active']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Deposit amount [$5] is outside permitted range ($10.00 - $5000.00).');

        app(CryptoInvoiceService::class)->createInvoice($user, 5, 'USDT', 'TRC20');
    }

    public function test_ad_delivery_service_recovers_from_invalid_cache_entries_without_type_error(): void
    {
        $slot = 'related_listings';
        $incomplete = unserialize('O:16:"MissingStubModel":0:{}');
        Cache::put('ads:slot_config_data:v1:' . $slot, $incomplete, 300);

        $config = app(AdDeliveryService::class)->getPlacementConfig($slot);

        $this->assertInstanceOf(AdPlacementConfig::class, $config);
        $this->assertSame($slot, $config->slot);
        $this->assertIsArray(Cache::get('ads:slot_config_data:v1:' . $slot));
    }
}
