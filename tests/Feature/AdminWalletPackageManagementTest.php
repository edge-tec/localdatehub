<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Monetization\Models\CreditPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminWalletPackageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\PostingMonetizationSeeder::class);
    }

    public function test_admin_can_view_packages_in_enterprise_hub(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('setPaymentSubTab', 'packages')
            ->assertSee('Starter Pack')
            ->assertSee('Popular Pack')
            ->assertSee('Pro Power Pack')
            ->assertSee('Elite Advertiser Pack');
    }

    public function test_admin_can_create_new_credit_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('setPaymentSubTab', 'packages')
            ->call('openCreatePackageModal')
            ->set('packageForm.name', 'Mega Enterprise VIP Pack')
            ->set('packageForm.credits', 5000)
            ->set('packageForm.bonus_credits', 1500)
            ->set('packageForm.price_usd', '199.99')
            ->set('packageForm.display_order', 10)
            ->set('packageForm.is_popular', true)
            ->set('packageForm.is_active', true)
            ->call('savePackage')
            ->assertDispatched('toast', type: 'success');

        $this->assertDatabaseHas('credit_packages', [
            'name' => 'Mega Enterprise VIP Pack',
            'credits' => 5000,
            'bonus_credits' => 1500,
            'price_usd' => 199.99,
            'is_popular' => true,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_edit_existing_credit_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = CreditPackage::first();

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('setPaymentSubTab', 'packages')
            ->call('openEditPackageModal', $package->id)
            ->set('packageForm.name', 'Updated Starter Bundle')
            ->set('packageForm.price_usd', '6.99')
            ->set('packageForm.credits', 75)
            ->set('packageForm.bonus_credits', 10)
            ->call('savePackage')
            ->assertDispatched('toast', type: 'success');

        $package->refresh();
        $this->assertEquals('Updated Starter Bundle', $package->name);
        $this->assertEquals('6.99', (string) $package->price_usd);
        $this->assertEquals(75, $package->credits);
        $this->assertEquals(10, $package->bonus_credits);
    }

    public function test_admin_can_toggle_package_off_and_it_disappears_from_user_checkout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $package = CreditPackage::where('name', 'Starter Pack')->first();
        $this->assertTrue((bool) $package->is_active);

        // 1. Verify user can see package on /account/wallet initially
        $response = $this->actingAs($user)->get(route('account.wallet.index'));
        $response->assertOk();
        $response->assertSee('Starter Pack');

        // 2. Admin toggles package OFF
        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('togglePackageActive', $package->id)
            ->assertDispatched('toast', type: 'success');

        $package->refresh();
        $this->assertFalse((bool) $package->is_active);

        // 3. User can no longer see package on /account/wallet
        $responseAfter = $this->actingAs($user)->get(route('account.wallet.index'));
        $responseAfter->assertOk();
        $responseAfter->assertDontSee('Starter Pack');

        // 4. Direct access to checkout for deactivated package returns 404
        $checkoutResponse = $this->actingAs($user)->get(route('account.wallet.checkout', ['package' => $package->id]));
        $checkoutResponse->assertNotFound();

        // 5. Admin toggles package back ON
        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('togglePackageActive', $package->id)
            ->assertDispatched('toast', type: 'success');

        $package->refresh();
        $this->assertTrue((bool) $package->is_active);

        // 6. User can see package again
        $responseReactivated = $this->actingAs($user)->get(route('account.wallet.index'));
        $responseReactivated->assertOk();
        $responseReactivated->assertSee('Starter Pack');
    }

    public function test_admin_can_delete_credit_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = CreditPackage::create([
            'name' => 'Temporary Obsolete Pack',
            'credits' => 10,
            'bonus_credits' => 0,
            'price_usd' => 1.99,
            'display_order' => 99,
            'is_active' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('deletePackage', $package->id)
            ->assertDispatched('toast', type: 'success');

        $this->assertDatabaseMissing('credit_packages', [
            'id' => $package->id,
            'name' => 'Temporary Obsolete Pack',
        ]);
    }

    public function test_standalone_controller_routes_can_toggle_edit_and_delete_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = CreditPackage::first();

        // Toggle via controller route
        $toggleRes = $this->actingAs($admin)->post(route('admin.wallet.packages.toggle', ['package' => $package->id]));
        $toggleRes->assertRedirect();
        $package->refresh();
        $this->assertFalse((bool) $package->is_active);

        // Update via controller route
        $updateRes = $this->actingAs($admin)->put(route('admin.wallet.packages.update', ['package' => $package->id]), [
            'name' => 'Controller Updated Pack',
            'credits' => 88,
            'bonus_credits' => 12,
            'price_usd' => '7.99',
            'display_order' => 1,
            'is_popular' => 1,
            'is_active' => 1,
        ]);
        $updateRes->assertRedirect();
        $package->refresh();
        $this->assertEquals('Controller Updated Pack', $package->name);
        $this->assertTrue((bool) $package->is_active);

        // Delete via controller route
        $delRes = $this->actingAs($admin)->delete(route('admin.wallet.packages.delete', ['package' => $package->id]));
        $delRes->assertRedirect();
        $this->assertDatabaseMissing('credit_packages', ['id' => $package->id]);
    }

    public function test_regular_user_cannot_modify_packages(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $package = CreditPackage::first();

        $this->actingAs($user)->post(route('admin.wallet.packages.toggle', ['package' => $package->id]))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.wallet.packages.delete', ['package' => $package->id]))->assertForbidden();
    }
}
