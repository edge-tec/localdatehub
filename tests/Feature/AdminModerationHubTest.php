<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Moderation\Models\AppealRequest;
use App\Modules\Moderation\Models\ContentReport;
use App\Modules\Moderation\Models\UserSuspension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminModerationHubTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Listing $pendingListing;
    protected AppealRequest $appeal;

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
            'name' => 'Charlie Chaplin',
            'email' => 'charlie.appeal@example.com',
            'role' => 'user',
            'account_status' => 'suspended',
            'is_age_verified' => true,
        ]);

        $category = Category::firstOrCreate(['slug' => 'casual-dating'], ['name' => 'Casual Dating', 'is_active' => true]);
        $state = State::firstOrCreate(['code' => 'CA'], ['name' => 'California', 'is_active' => true]);
        $city = City::firstOrCreate(['slug' => 'los-angeles', 'state_id' => $state->id], ['name' => 'Los Angeles', 'is_active' => true]);

        $this->pendingListing = Listing::create([
            'user_id' => $this->regularUser->id,
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'title' => 'Fun and exciting weekend companion in LA',
            'slug' => 'fun-and-exciting-weekend-companion-in-la',
            'description' => 'Looking for coffee and adventures around Santa Monica Pier.',
            'status' => 'pending_review',
            'is_featured' => false,
            'is_verified_poster' => true,
            'poster_age' => 26,
            'poster_gender' => 'Male',
        ]);

        $suspension = UserSuspension::create([
            'user_id' => $this->regularUser->id,
            'issued_by_admin_id' => $this->admin->id,
            'reason' => 'Suspicious automated contact activity',
            'suspension_type' => 'temporary',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
            'is_lifted' => false,
        ]);

        $this->appeal = AppealRequest::create([
            'user_id' => $this->regularUser->id,
            'user_suspension_id' => $suspension->id,
            'appeal_reason' => 'My account was accessed from a VPN while traveling. I have secured my credentials.',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_view_moderation_hub_with_kpi_metrics_and_tabs(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'moderation'])
            ->assertSee('Safety Moderation Center')
            ->assertSee('Pending Review')
            ->assertSee('Suspension Appeals')
            ->assertSee('Safety Reports')
            ->assertSee('Pending Review Queue')
            ->assertSee('Fun and exciting weekend companion in LA')
            ->assertSee('Charlie Chaplin');
    }

    public function test_admin_can_filter_moderation_queue_by_search(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'moderation'])
            ->set('moderationSearch', 'Santa Monica')
            ->assertSee('Fun and exciting weekend companion in LA')
            ->set('moderationSearch', 'Nonexistent keyword 123xyz')
            ->assertSee('Review Queue Is Clean');
    }

    public function test_admin_can_approve_pending_listing_from_moderation_queue(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'moderation'])
            ->call('approveListing', $this->pendingListing->id)
            ->assertHasNoErrors();

        $this->pendingListing->refresh();
        $this->assertSame('active', $this->pendingListing->status);
    }

    public function test_admin_can_bulk_approve_listings_from_moderation_queue(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'moderation'])
            ->set('selectedQueueListingIds', [$this->pendingListing->id])
            ->call('bulkApproveQueue')
            ->assertHasNoErrors();

        $this->pendingListing->refresh();
        $this->assertSame('active', $this->pendingListing->status);
    }

    public function test_admin_can_switch_subtabs_and_view_appeals(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'moderation'])
            ->call('setModerationSubTab', 'appeals')
            ->assertSet('moderationSubTab', 'appeals')
            ->assertSee('Charlie Chaplin')
            ->assertSee('My account was accessed from a VPN while traveling')
            ->assertSee('Review Appeal');
    }

    public function test_admin_can_open_and_grant_suspension_appeal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'moderation'])
            ->call('openAppealModal', $this->appeal->id)
            ->assertSet('showAppealModal', true)
            ->assertSee('Review Suspension Appeal #' . $this->appeal->id)
            ->assertSee('Charlie Chaplin')
            ->assertSee('Suspicious automated contact activity')
            ->set('appealDecisionNotes', 'Account verified by security team. Reinstating account.')
            ->call('decideAppeal', 'granted')
            ->assertHasNoErrors()
            ->assertSet('showAppealModal', false);

        $this->appeal->refresh();
        $this->assertSame('granted', $this->appeal->status);
        $this->regularUser->refresh();
        $this->assertSame('active', $this->regularUser->account_status);
    }

    public function test_admin_can_view_safety_reports_subtab(): void
    {
        ContentReport::create([
            'reporter_id' => $this->admin->id,
            'reportable_type' => Listing::class,
            'reportable_id' => $this->pendingListing->id,
            'reason_code' => 'commercial_solicitation',
            'risk_level' => 'high',
            'is_urgent_takedown' => false,
            'details' => 'Soliciting commercial services in personals section.',
            'status' => 'open',
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'moderation'])
            ->call('setModerationSubTab', 'reports')
            ->assertSet('moderationSubTab', 'reports')
            ->assertSee('Commercial Solicitation')
            ->assertSee('Soliciting commercial services in personals section.');
    }

    public function test_admin_can_inspect_listing_with_status_history(): void
    {
        \App\Modules\Listings\Models\ListingStatusHistory::create([
            'listing_id' => $this->pendingListing->id,
            'from_status' => 'draft',
            'to_status' => 'pending_review',
            'reason' => 'Submitted for moderation review',
            'changed_by_user_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'moderation'])
            ->call('openViewListingModal', $this->pendingListing->id)
            ->assertSet('showViewListingModal', true)
            ->assertSet('viewListingData.id', $this->pendingListing->id)
            ->assertSet('viewListingData.status_history.0.actor', $this->admin->name);
    }

    public function test_admin_can_view_and_reply_to_support_ticket_in_real_time(): void
    {
        $ticket = \App\Modules\Contact\Models\ContactMessage::create([
            'listing_id' => null,
            'sender_id' => $this->regularUser->id,
            'inquiry_type' => 'account_profile',
            'sender_name' => $this->regularUser->name,
            'sender_email_encrypted' => $this->regularUser->email,
            'subject' => 'Need help verifying my profile',
            'message_encrypted' => 'Hello support, please check my account verification status.',
            'is_read' => false,
            'is_replied' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'support_tickets'])
            ->assertSee('Need help verifying my profile')
            ->call('inspectSupportTicket', $ticket->id)
            ->assertSet('showSupportTicketModal', true)
            ->assertSet('inspectSupportTicketData.id', $ticket->id)
            ->set('supportReplyText', 'We have reviewed your profile and updated your verification status.')
            ->call('replyToSupportTicket')
            ->assertHasNoErrors()
            ->assertSee('We have reviewed your profile and updated your verification status.');

        $ticket->refresh();
        $this->assertTrue($ticket->is_read);
        $this->assertTrue($ticket->is_replied);
        $this->assertSame(1, $ticket->replies()->count());
        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $this->regularUser->id,
            'notification_type' => 'support_ticket_reply',
        ]);
    }
}
