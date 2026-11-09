<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Admin\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class AdminStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $moderator;
    protected User $supportStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::factory()->create([
            'name' => 'Chief Admin',
            'email' => 'chief.admin@localdatehub.com',
            'role' => 'admin',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->moderator = User::factory()->create([
            'name' => 'Mod Sandra',
            'email' => 'sandra.mod@localdatehub.com',
            'role' => 'senior_moderator',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->supportStaff = User::factory()->create([
            'name' => 'Support Dan',
            'email' => 'dan.support@localdatehub.com',
            'role' => 'customer_support',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);
    }

    public function test_admin_can_view_staff_management_hub_with_kpi_cards_and_accounts(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'staff'])
            ->assertOk()
            ->assertSee('Total Staff Team')
            ->assertSee('Administrators')
            ->assertSee('Content Moderators')
            ->assertSee('Specialist Officers')
            ->assertSee('+ Add Staff Account')
            ->assertSee('Chief Admin')
            ->assertSee('chief.admin@localdatehub.com')
            ->assertSee('Mod Sandra')
            ->assertSee('sandra.mod@localdatehub.com')
            ->assertSee('Support Dan')
            ->assertSee('dan.support@localdatehub.com')
            ->assertSee('Login As');
    }

    public function test_admin_can_filter_and_search_staff_accounts(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'staff'])
            ->set('staffSearch', 'Sandra')
            ->assertSee('Mod Sandra')
            ->assertDontSee('Support Dan')
            ->set('staffSearch', '')
            ->set('staffRoleFilter', 'customer_support')
            ->assertSee('Support Dan')
            ->assertDontSee('Mod Sandra')
            ->call('resetStaffFilters')
            ->assertSee('Mod Sandra')
            ->assertSee('Support Dan');
    }

    public function test_admin_can_add_new_staff_account(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'staff'])
            ->call('openCreateStaffModal')
            ->assertSet('showCreateUserModal', true)
            ->assertSet('createUserRole', 'admin')
            ->set('createUserName', 'Officer John Doe')
            ->set('createUserDisplayName', 'Officer John')
            ->set('createUserEmail', 'john.compliance@localdatehub.com')
            ->set('createUserPassword', 'SecretPass123!')
            ->set('createUserRole', 'compliance_officer')
            ->set('createUserAccountStatus', 'active')
            ->set('createUserIsAgeVerified', true)
            ->call('createUser')
            ->assertSet('showCreateUserModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Officer John Doe',
            'email' => 'john.compliance@localdatehub.com',
            'role' => 'compliance_officer',
            'account_status' => 'active',
        ]);
    }

    public function test_admin_can_view_staff_account_details_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'staff'])
            ->assertSet('showViewUserModal', false)
            ->call('openViewStaffModal', $this->moderator->id)
            ->assertSet('showViewUserModal', true)
            ->assertSee('Mod Sandra')
            ->assertSee('sandra.mod@localdatehub.com')
            ->call('closeModals')
            ->assertSet('showViewUserModal', false);
    }

    public function test_admin_can_edit_staff_member_details(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'staff'])
            ->call('openEditStaffModal', $this->supportStaff->id)
            ->assertSet('showEditUserModal', true)
            ->set('editUserName', 'Daniel Lead Support')
            ->set('editUserDisplayName', 'Dan Lead')
            ->set('editUserRole', 'customer_support')
            ->set('editUserAccountStatus', 'active')
            ->call('updateUser')
            ->assertSet('showEditUserModal', false)
            ->assertHasNoErrors();

        $this->supportStaff->refresh();
        $this->assertEquals('Daniel Lead Support', $this->supportStaff->name);
    }

    public function test_admin_can_delete_staff_member(): void
    {
        $tempStaff = User::factory()->create([
            'name' => 'Temp Intern',
            'email' => 'temp.intern@localdatehub.com',
            'role' => 'listing_moderator',
            'account_status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'staff'])
            ->call('openDeleteStaffModal', $tempStaff->id)
            ->assertSet('showDeleteUserModal', true)
            ->call('deleteUser')
            ->assertSet('showDeleteUserModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('users', [
            'id' => $tempStaff->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_direct_login_to_staff_account_without_password_and_exit(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'staff'])
            ->call('directLoginAsStaff', $this->moderator->id);

        // Check authentication switched to moderator
        $this->assertEquals($this->moderator->id, Auth::id());
        $this->assertEquals($this->admin->id, session('impersonator_admin_id'));

        // Check exiting impersonation session
        $component->call('stopImpersonation');

        // Check authentication restored to original admin
        $this->assertEquals($this->admin->id, Auth::id());
        $this->assertFalse(session()->has('impersonator_admin_id'));
    }

    public function test_admin_cannot_direct_login_to_their_own_account(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'staff'])
            ->call('directLoginAsStaff', $this->admin->id)
            ->assertHasErrors(['impersonate']);
    }
}
