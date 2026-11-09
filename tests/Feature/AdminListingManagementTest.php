<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminListingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $posterUser;
    protected Category $category;
    protected State $state;
    protected City $city;
    protected Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->posterUser = User::factory()->create([
            'name' => 'Samantha Jones',
            'email' => 'samantha.poster@example.com',
            'role' => 'user',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->category = Category::firstOrCreate(
            ['slug' => 'women-seeking-men'],
            ['name' => 'Women Seeking Men', 'is_active' => true]
        );

        $this->state = State::firstOrCreate(
            ['code' => 'NY'],
            ['name' => 'New York', 'is_active' => true]
        );

        $this->city = City::firstOrCreate(
            ['slug' => 'new-york-city', 'state_id' => $this->state->id],
            ['name' => 'New York City', 'is_active' => true]
        );

        $this->listing = Listing::create([
            'user_id' => $this->posterUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
            'title' => 'Charming and witty romantic in Manhattan',
            'slug' => 'charming-and-witty-romantic-in-manhattan',
            'description' => 'Looking for genuine connections, good wine, and theater evenings.',
            'status' => 'active',
            'is_featured' => false,
            'is_verified_poster' => true,
            'poster_age' => 28,
            'poster_gender' => 'Female',
            'intent' => 'Long-term dating',
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function test_admin_can_view_listings_tab_with_clickable_owner_and_action_buttons(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'listings'])
            ->assertSee('Charming and witty romantic in Manhattan')
            ->assertSee('Samantha Jones')
            ->assertSee('samantha.poster@example.com')
            ->assertSee('View Owner Profile')
            ->assertSee('View')
            ->assertSee('Edit')
            ->assertSee('Delete');
    }

    public function test_admin_can_view_listing_details_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'listings'])
            ->call('openViewListingModal', $this->listing->id)
            ->assertSet('showViewListingModal', true)
            ->assertSee('Listing Details #' . $this->listing->id)
            ->assertSee('Charming and witty romantic in Manhattan')
            ->assertSee('Samantha Jones')
            ->assertSee('samantha.poster@example.com')
            ->assertSee('Inspect User Profile')
            ->assertSee('Women Seeking Men')
            ->assertSee('New York City')
            ->assertSee('Looking for genuine connections, good wine, and theater evenings.');
    }

    public function test_admin_can_open_owner_details_from_listing_owner_link(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'listings'])
            ->call('openViewUserModal', $this->posterUser->id)
            ->assertSet('showViewUserModal', true)
            ->assertSee('Samantha Jones')
            ->assertSee('samantha.poster@example.com')
            ->assertSee('Public Display Name:');
    }

    public function test_admin_can_open_edit_listing_modal_and_update_listing(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'listings'])
            ->call('openEditListingModal', $this->listing->id)
            ->assertSet('showEditListingModal', true)
            ->assertSet('editListingTitle', 'Charming and witty romantic in Manhattan')
            ->set('editListingTitle', 'Updated Romantic Journey in NYC')
            ->set('editListingDescription', 'Updated detailed description for testing administrative updates.')
            ->set('editListingIsFeatured', true)
            ->set('editListingStatus', 'active')
            ->call('updateListing')
            ->assertHasNoErrors()
            ->assertSet('showEditListingModal', false);

        $this->listing->refresh();
        $this->assertSame('Updated Romantic Journey in NYC', $this->listing->title);
        $this->assertSame('Updated detailed description for testing administrative updates.', $this->listing->description);
        $this->assertTrue((bool) $this->listing->is_featured);
    }

    public function test_admin_can_open_delete_listing_modal_and_confirm_soft_deletion(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'listings'])
            ->call('openDeleteListingModal', $this->listing->id)
            ->assertSet('showDeleteListingModal', true)
            ->assertSet('deleteListingId', $this->listing->id)
            ->call('deleteListingConfirmed')
            ->assertSet('showDeleteListingModal', false);

        $this->assertSoftDeleted('listings', ['id' => $this->listing->id]);
    }
}

