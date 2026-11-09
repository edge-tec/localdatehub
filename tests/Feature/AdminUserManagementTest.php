<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Admin\Models\Role;
use App\Modules\Listings\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'role' => 'user',
            'account_status' => 'active',
            'is_age_verified' => false,
        ]);
    }

    public function test_admin_can_view_users_list_with_all_action_buttons(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->assertSee('User Management')
            ->assertSee('Create User')
            ->assertSee('John Doe')
            ->assertSee('john.doe@example.com')
            ->assertSee('View')
            ->assertSee('Edit')
            ->assertSee('Delete');
    }

    public function test_admin_can_create_a_new_user(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->call('openCreateUserModal')
            ->assertSet('showCreateUserModal', true)
            ->set('createUserName', 'Alice Cooper')
            ->set('createUserDisplayName', 'AliceC')
            ->set('createUserEmail', 'alice@example.com')
            ->set('createUserPassword', 'SecretPass123!')
            ->set('createUserPhone', '+1 555 123 4567')
            ->set('createUserRole', 'user')
            ->set('createUserAccountStatus', 'active')
            ->set('createUserIsAgeVerified', true)
            ->set('createUserCredits', 50)
            ->call('createUser')
            ->assertHasNoErrors()
            ->assertSet('showCreateUserModal', false);

        $created = User::where('email', 'alice@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame('Alice Cooper', $created->name);
        $this->assertTrue((bool) $created->is_age_verified);
        $this->assertTrue(Hash::check('SecretPass123!', $created->password));
        $this->assertSame('AliceC', $created->profile?->display_name);
        $this->assertSame(50, $created->wallet?->balance);
    }

    public function test_admin_can_view_user_details(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->call('openViewUserModal', $this->regularUser->id)
            ->assertSet('showViewUserModal', true)
            ->assertSee('John Doe')
            ->assertSee('john.doe@example.com')
            ->assertSee('Public Display Name:');
    }

    public function test_admin_can_edit_user_details(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->call('openEditUserModal', $this->regularUser->id)
            ->assertSet('showEditUserModal', true)
            ->assertSet('editUserName', 'John Doe')
            ->set('editUserName', 'John Updated')
            ->set('editUserDisplayName', 'JohnnyBoy')
            ->set('editUserEmail', 'john.updated@example.com')
            ->set('editUserRole', 'advertiser')
            ->set('editUserIsAgeVerified', true)
            ->set('editUserCredits', 75)
            ->call('updateUser')
            ->assertHasNoErrors()
            ->assertSet('showEditUserModal', false);

        $this->regularUser->refresh();
        $this->assertSame('John Updated', $this->regularUser->name);
        $this->assertSame('john.updated@example.com', $this->regularUser->email);
        $this->assertSame('advertiser', $this->regularUser->role);
        $this->assertTrue((bool) $this->regularUser->is_age_verified);
        $this->assertSame('JohnnyBoy', $this->regularUser->profile?->display_name);
        $this->assertSame(75, $this->regularUser->wallet?->balance);
    }

    public function test_admin_can_delete_a_user(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->call('openDeleteUserModal', $this->regularUser->id)
            ->assertSet('showDeleteUserModal', true)
            ->call('deleteUser')
            ->assertHasNoErrors()
            ->assertSet('showDeleteUserModal', false);

        $this->assertSoftDeleted('users', ['id' => $this->regularUser->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->call('openDeleteUserModal', $this->admin->id)
            ->assertStatus(403);
    }

    public function test_admin_can_direct_login_as_user_without_password_and_return_to_admin(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->assertSee('Login')
            ->call('directLoginAsUser', $this->regularUser->id)
            ->assertRedirect(route('account.dashboard'));

        // Check authentication switched to regular user
        $this->assertSame($this->regularUser->id, auth()->id());
        $this->assertSame($this->admin->id, session('impersonator_admin_id'));
        $this->assertSame($this->admin->name, session('impersonator_admin_name'));

        // Visiting dashboard shows Impersonation Banner
        $response = $this->get(route('account.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Impersonation Active');
        $response->assertSee('Exit & Return to Admin');

        // Test stopping impersonation
        $stopResponse = $this->get(route('admin.impersonate.stop'));
        $stopResponse->assertRedirect(route('admin.index', ['tab' => 'users']));

        // Check authentication restored to admin
        $this->assertSame($this->admin->id, auth()->id());
        $this->assertNull(session('impersonator_admin_id'));
    }

    public function test_admin_cannot_direct_login_as_themselves(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->call('directLoginAsUser', $this->admin->id)
            ->assertHasErrors(['impersonate']);
    }
}
