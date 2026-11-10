<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Notifications\Models\EmailTemplate;
use App\Modules\Notifications\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVerificationAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->user = User::factory()->create([
            'name' => 'John Candidate',
            'email' => 'john.candidate@test.com',
            'role' => 'user',
            'account_status' => 'active',
            'is_age_verified' => false,
        ]);
    }

    public function test_admin_can_view_verification_queue_and_inspect_record(): void
    {
        $assurance = AgeAssurance::create([
            'user_id' => $this->user->id,
            'method' => 'digital_id',
            'status' => 'pending',
            'declared_birthdate' => '1996-06-15',
            'third_party_reference' => 'KYC-TEST-7788',
            'verification_metadata' => ['ip' => '127.0.0.1'],
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'verification'])
            ->assertSee('Age Assurance')
            ->assertSee('Pending Review')
            ->assertSee('John Candidate')
            ->assertSee('DIGITAL ID')
            ->assertSee('KYC-TEST-7788');

        // Inspect modal
        $component->call('openInspectVerificationModal', $assurance->id)
            ->assertSet('showInspectVerificationModal', true)
            ->assertSee('Age Assurance Record #' . $assurance->id);

        // Approve
        $component->call('approveAgeVerification', $assurance->id)
            ->assertSet('showInspectVerificationModal', false);

        $this->assertEquals('approved', $assurance->fresh()->status);
        $this->assertTrue((bool) $this->user->fresh()->is_age_verified);
    }

    public function test_admin_can_manually_verify_user_age(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'verification'])
            ->call('openManualVerifyModal')
            ->assertSet('showManualVerifyModal', true)
            ->set('manualVerifyForm.user_id', $this->user->id)
            ->set('manualVerifyForm.declared_birthdate', '1997-03-20')
            ->set('manualVerifyForm.method', 'digital_id')
            ->set('manualVerifyForm.admin_notes', 'Verified via in-person check')
            ->call('saveManualVerification')
            ->assertSet('showManualVerifyModal', false)
            ->assertHasNoErrors();

        $this->assertTrue((bool) $this->user->fresh()->is_age_verified);
        $this->assertDatabaseHas('age_assurances', [
            'user_id' => $this->user->id,
            'status' => 'approved',
            'method' => 'digital_id',
        ]);
    }

    public function test_admin_can_view_notifications_and_dispatch_broadcast(): void
    {
        $log = NotificationLog::create([
            'user_id' => $this->user->id,
            'channel' => 'email',
            'notification_type' => 'test_alert',
            'subject' => 'System Maintenance Window',
            'recipient_target' => $this->user->email,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'notifications'])
            ->assertSee('Delivered Notifications')
            ->assertSee('System Maintenance Window')
            ->assertSee('EMAIL');

        // Open Broadcast modal
        $component->call('openBroadcastModal')
            ->assertSet('showBroadcastModal', true)
            ->set('broadcastForm.target_audience', 'all_users')
            ->set('broadcastForm.channel', 'database')
            ->set('broadcastForm.subject', 'Community Rules Update')
            ->set('broadcastForm.message', 'Please review the updated community guidelines.')
            ->call('sendBroadcastNotification')
            ->assertSet('showBroadcastModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifications_log', [
            'subject' => 'Community Rules Update',
            'channel' => 'database',
            'status' => 'sent',
        ]);
    }

    public function test_admin_can_toggle_email_template(): void
    {
        $tpl = EmailTemplate::create([
            'slug' => 'test_welcome_tpl',
            'name' => 'Welcome Template Test',
            'subject' => 'Welcome to LocalDateHub',
            'body_html' => '<p>Hello</p>',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'notifications'])
            ->call('setNotificationsSubTab', 'templates')
            ->assertSet('notificationsSubTab', 'templates')
            ->assertSee('Welcome Template Test')
            ->call('toggleTemplate', $tpl->id);

        $this->assertFalse((bool) $tpl->fresh()->is_active);
    }

    public function test_admin_can_manage_cms_pages_with_preview_and_toggle(): void
    {
        $page = \App\Modules\SEO\Models\CmsPage::create([
            'slug' => 'compliance-2257',
            'title' => 'Federal 2257 Compliance Record',
            'content' => '<p>Custodian of records documentation.</p>',
            'is_published' => true,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'cms'])
            ->assertSee('Total CMS Pages')
            ->assertSee('Live & Published', false)
            ->assertSee('Federal 2257 Compliance Record')
            ->assertSee('/page/compliance-2257');

        // Preview modal
        $component->call('openPreviewCmsModal', $page->id)
            ->assertSet('showPreviewCmsModal', true)
            ->assertSee('Custodian of records documentation', false);

        // Toggle publication
        $component->call('toggleCmsPublish', $page->id);
        $this->assertFalse((bool) $page->fresh()->is_published);

        // Delete page
        $component->call('deleteCmsPage', $page->id);
        $this->assertDatabaseMissing('cms_pages', ['id' => $page->id]);
    }

    public function test_admin_can_manage_advertising_placements_with_filters_and_deletion(): void
    {
        $ad = \App\Modules\Admin\Models\AdPlacement::create([
            'name' => 'Elite Escort Agency Banner',
            'slot' => 'sidebar',
            'advertiser_name' => 'Elite Brand Group',
            'target_url' => 'https://elitebrand.com',
            'is_active' => true,
            'impressions' => 1250,
            'clicks' => 65,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'ads'])
            ->assertSee('Total Campaigns')
            ->assertSee('Active Slots')
            ->assertSee('Total Impressions')
            ->assertSee('Avg. CTR')
            ->assertSee('Elite Escort Agency Banner')
            ->assertSee('Elite Brand Group');

        // Toggle pause
        $component->call('toggleAd', $ad->id);
        $this->assertFalse((bool) $ad->fresh()->is_active);

        // Preview modal
        $component->call('openPreviewAdModal', $ad->id)
            ->assertSet('showPreviewAdModal', true)
            ->assertSee('Live Creative Simulation');

        // Clone ad
        $component->call('cloneAd', $ad->id);
        $this->assertDatabaseHas('ad_placements', ['name' => 'Elite Escort Agency Banner (Copy)']);

        // Reset stats
        $component->call('resetAdStats', $ad->id);
        $this->assertSame(0, $ad->fresh()->impressions);
        $this->assertSame(0, $ad->fresh()->clicks);

        // Subtabs
        $component->call('setAdSubTab', 'inventory')
            ->assertSee('Slot Architecture')
            ->call('setAdSubTab', 'analytics')
            ->assertSee('Performance Analytics');

        // Delete ad
        $component->call('confirmDeleteAd', $ad->id)
            ->assertSet('showDeleteAdModal', true)
            ->call('deleteAd');

        $this->assertDatabaseMissing('ad_placements', ['id' => $ad->id]);
    }

    public function test_admin_can_inspect_and_approve_id_card_verification_granting_verified_badge(): void
    {
        $verification = \App\Modules\Verification\Models\IdentityVerification::create([
            'user_id' => $this->user->id,
            'document_type' => 'drivers_license',
            'document_front_path' => 'verifications/test_front.jpg',
            'document_back_path' => 'verifications/test_back.jpg',
            'selfie_path' => 'verifications/test_selfie.jpg',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        // 1. Admin views queue
        $response = $this->actingAs($this->admin)->get(route('admin.verifications.index'));
        $response->assertStatus(200);
        $response->assertSee('John Candidate');
        $response->assertSee('drivers license', false);

        // 2. Admin inspects application
        $showResponse = $this->actingAs($this->admin)->get(route('admin.verifications.show', $verification));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Review: John Candidate');
        $showResponse->assertSee('Approve & Verify');

        // 3. Admin approves application
        $approveResponse = $this->actingAs($this->admin)->post(route('admin.verifications.approve', $verification), [
            'admin_notes' => 'Government ID matches facial selfie perfectly.',
        ]);
        $approveResponse->assertRedirect(route('admin.verifications.index'));

        // 4. Verify user and verification records updated
        $this->assertEquals('verified', $verification->fresh()->status);
        $this->assertTrue((bool) $this->user->fresh()->is_identity_verified);
        $this->assertEquals($this->admin->id, $verification->fresh()->reviewer_id);
    }

    public function test_admin_realtime_notification_feed_aggregates_id_verifications_listings_and_messages(): void
    {
        // 1. Create a pending ID verification
        \App\Modules\Verification\Models\IdentityVerification::create([
            'user_id' => $this->user->id,
            'document_type' => 'passport',
            'document_front_path' => 'verifications/front.jpg',
            'selfie_path' => 'verifications/selfie.jpg',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        // 2. Create a pending listing
        $category = \App\Modules\Categories\Models\Category::first();
        $city = \App\Modules\Locations\Models\City::first();
        $listing = \App\Modules\Listings\Models\Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $category?->id ?? 1,
            'city_id' => $city?->id ?? 1,
            'state_id' => $city?->state_id ?? 1,
            'title' => 'Seeking evening date in Miami',
            'slug' => 'seeking-evening-date-' . uniqid(),
            'description' => 'Looking for pleasant conversation and nice dinner.',
            'status' => 'pending',
            'poster_age' => 28,
        ]);

        // 3. Create an unread contact message
        \App\Modules\Contact\Models\ContactMessage::create([
            'sender_id' => $this->user->id,
            'recipient_id' => $this->admin->id,
            'inquiry_type' => 'general_support',
            'sender_name' => 'Alice Member',
            'sender_email_encrypted' => 'alice@test.com',
            'subject' => 'Urgent Account Question',
            'message_encrypted' => 'I need help with my verification badge.',
            'is_read' => false,
        ]);

        // Hit the real-time notification feed endpoint
        $response = $this->actingAs($this->admin)->get(route('admin.notifications.feed'));
        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
        ]);

        $data = $response->json();
        $this->assertGreaterThanOrEqual(3, $data['total_unread']);
        $this->assertGreaterThanOrEqual(1, $data['counts']['id_verifications']);
        $this->assertGreaterThanOrEqual(1, $data['counts']['pending_listings']);
        $this->assertGreaterThanOrEqual(1, $data['counts']['messages']);

        // Check feed items contain our elements
        $itemsCollection = collect($data['items']);
        $this->assertTrue($itemsCollection->contains(fn($item) => str_contains($item['title'], 'John Candidate') || $item['category'] === 'id_verification'));
        $this->assertTrue($itemsCollection->contains(fn($item) => str_contains($item['title'], 'Seeking evening date') || $item['category'] === 'listing_pending'));
        $this->assertTrue($itemsCollection->contains(fn($item) => str_contains($item['title'], 'Alice Member') || $item['category'] === 'contact_message'));
    }
}
