<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Admin\Models\AdminAuditLog;
use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Admin\Models\Role;
use App\Modules\Admin\Models\SystemSetting;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Billing\Models\Refund;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Moderation\Models\ContentReport;
use App\Modules\Moderation\Models\UserSuspension;
use App\Modules\SEO\Models\CmsPage;
use App\Modules\SEO\Models\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class EnterpriseAdminSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $listingModerator;
    protected User $financeManager;
    protected User $complianceOfficer;
    protected User $seoManager;
    protected User $regularUser;
    protected Category $category;
    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->category = Category::first();
        $this->city = City::first();

        // 1. Super Admin
        $this->superAdmin = User::factory()->create([
            'name' => 'Root SuperAdmin',
            'email' => 'superadmin@example.com',
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        // 2. Listing Moderator
        $this->listingModerator = User::factory()->create([
            'name' => 'Listing Moderator',
            'email' => 'moderator@example.com',
            'role' => 'listing_moderator',
            'account_status' => 'active',
        ]);
        $moderatorRole = Role::where('slug', 'listing_moderator')->first();
        if ($moderatorRole) {
            $this->listingModerator->roles()->sync([$moderatorRole->id]);
        }

        // 3. Finance Manager
        $this->financeManager = User::factory()->create([
            'name' => 'Finance Manager',
            'email' => 'finance@example.com',
            'role' => 'finance_manager',
            'account_status' => 'active',
        ]);
        $financeRole = Role::where('slug', 'finance_manager')->first();
        if ($financeRole) {
            $this->financeManager->roles()->sync([$financeRole->id]);
        }

        // 4. Compliance Officer
        $this->complianceOfficer = User::factory()->create([
            'name' => 'Compliance Officer',
            'email' => 'compliance@example.com',
            'role' => 'compliance_officer',
            'account_status' => 'active',
        ]);
        $complianceRole = Role::where('slug', 'compliance_officer')->first();
        if ($complianceRole) {
            $this->complianceOfficer->roles()->sync([$complianceRole->id]);
        }

        // 5. SEO Manager
        $this->seoManager = User::factory()->create([
            'name' => 'SEO Manager',
            'email' => 'seo@example.com',
            'role' => 'seo_manager',
            'account_status' => 'active',
        ]);
        $seoRole = Role::where('slug', 'seo_manager')->first();
        if ($seoRole) {
            $this->seoManager->roles()->sync([$seoRole->id]);
        }

        // 6. Regular Member
        $this->regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'role' => 'user',
            'account_status' => 'active',
        ]);
    }

    public function test_unauthenticated_visitor_is_redirected_to_login_when_accessing_admin(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_standard_user_receives_403_forbidden_when_accessing_admin(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    public function test_suspended_staff_user_is_redirected_to_suspended_notice(): void
    {
        $this->listingModerator->update(['account_status' => 'suspended']);

        $response = $this->actingAs($this->listingModerator)->get(route('admin.dashboard'));
        $response->assertRedirect(route('account.suspended'));
    }

    public function test_super_admin_can_access_admin_dashboard_and_see_kpi_metrics(): void
    {
        Listing::create([
            'user_id' => $this->regularUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Pending Verification Listing',
            'slug' => 'pending-listing-' . uniqid(),
            'description' => 'Looking for coffee and genuine talks.',
            'content_hash' => hash('sha256', 'hash_test_' . uniqid()),
            'poster_age' => 26,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_PENDING_REVIEW,
            'view_count' => 15,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Enterprise Administration Hub', false);
        $response->assertSee('Total Accounts', false);
        $response->assertSee('System Health', false);
        $response->assertSee('Root SuperAdmin');
    }

    public function test_granular_rbac_listing_moderator_can_access_moderation_tab_but_forbidden_from_settings(): void
    {
        // Moderator can access dashboard & moderation
        $response = $this->actingAs($this->listingModerator)->get(route('admin.dashboard', ['tab' => 'moderation']));
        $response->assertStatus(200);
        $response->assertSee('Moderation');

        // Livewire component refuses switching to unauthorized settings tab
        Livewire::actingAs($this->listingModerator)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'dashboard'])
            ->call('setTab', 'settings')
            ->assertStatus(403);
    }

    public function test_granular_rbac_listing_moderator_forbidden_from_billing_refunds_and_system_settings(): void
    {
        // Attempting to invoke refund method directly
        Livewire::actingAs($this->listingModerator)
            ->test(AdminEnterpriseHub::class)
            ->call('openRefundModal', 1)
            ->assertStatus(403);

        // Attempting to invoke saveSystemSettings directly
        Livewire::actingAs($this->listingModerator)
            ->test(AdminEnterpriseHub::class)
            ->call('saveSystemSettings')
            ->assertStatus(403);
    }

    public function test_super_admin_can_suspend_and_unsuspend_a_user_creating_audit_log(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Flagged Misconduct User',
            'email' => 'flagged@example.com',
            'account_status' => 'active',
        ]);

        // Suspend user
        Livewire::actingAs($this->superAdmin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->call('openSuspendUserModal', $targetUser->id)
            ->set('userSuspendReason', 'Violation of terms regarding commercial services.')
            ->set('userSuspendType', 'temporary')
            ->set('userSuspendDays', 14)
            ->call('suspendUser')
            ->assertHasNoErrors();

        $this->assertEquals('suspended', $targetUser->fresh()->account_status);
        $this->assertDatabaseHas('user_suspensions', [
            'user_id' => $targetUser->id,
            'reason' => 'Violation of terms regarding commercial services.',
            'is_lifted' => false,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->superAdmin->id,
            'action' => 'user.suspend',
            'target_id' => $targetUser->id,
        ]);

        // Unsuspend user
        Livewire::actingAs($this->superAdmin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'users'])
            ->call('unsuspendUser', $targetUser->id)
            ->assertHasNoErrors();

        $this->assertEquals('active', $targetUser->fresh()->account_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->superAdmin->id,
            'action' => 'user.unsuspend',
            'target_id' => $targetUser->id,
        ]);
    }

    public function test_staff_can_approve_and_reject_listing_with_audit_trail(): void
    {
        $listing = Listing::create([
            'user_id' => $this->regularUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Fresh Dating Post to Review',
            'slug' => 'fresh-dating-post-' . uniqid(),
            'description' => 'Looking for coffee and great discussions.',
            'content_hash' => hash('sha256', 'hash_test2_' . uniqid()),
            'poster_age' => 24,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_PENDING_REVIEW,
        ]);

        // Approve listing
        Livewire::actingAs($this->listingModerator)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'listings'])
            ->call('approveListing', $listing->id)
            ->assertHasNoErrors();

        $this->assertEquals(Listing::STATUS_ACTIVE, $listing->fresh()->status);
        $this->assertNotNull($listing->fresh()->published_at);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->listingModerator->id,
            'action' => 'listing.approve',
            'target_id' => $listing->id,
        ]);

        // Reject listing
        Livewire::actingAs($this->listingModerator)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'listings'])
            ->call('openRejectListingModal', $listing->id)
            ->set('listingActionReason', 'Listing includes prohibited contact links.')
            ->call('rejectListing')
            ->assertHasNoErrors();

        $this->assertEquals(Listing::STATUS_REJECTED, $listing->fresh()->status);
        $this->assertEquals('Listing includes prohibited contact links.', $listing->fresh()->rejection_reason);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->listingModerator->id,
            'action' => 'listing.reject',
            'target_id' => $listing->id,
        ]);
    }

    public function test_staff_can_bulk_approve_listings_with_audit_trail(): void
    {
        $l1 = Listing::create([
            'user_id' => $this->regularUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Bulk Listing 1',
            'slug' => 'bulk-1-' . uniqid(),
            'description' => 'Bulk item one.',
            'content_hash' => hash('sha256', 'bulk1_' . uniqid()),
            'poster_age' => 29,
            'intent' => 'dating',
            'status' => Listing::STATUS_PENDING_REVIEW,
        ]);

        $l2 = Listing::create([
            'user_id' => $this->regularUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Bulk Listing 2',
            'slug' => 'bulk-2-' . uniqid(),
            'description' => 'Bulk item two.',
            'content_hash' => hash('sha256', 'bulk2_' . uniqid()),
            'poster_age' => 31,
            'intent' => 'dating',
            'status' => Listing::STATUS_PENDING_REVIEW,
        ]);

        Livewire::actingAs($this->listingModerator)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'listings'])
            ->set('selectedListingIds', [$l1->id, $l2->id])
            ->call('bulkApproveListings')
            ->assertHasNoErrors();

        $this->assertEquals(Listing::STATUS_ACTIVE, $l1->fresh()->status);
        $this->assertEquals(Listing::STATUS_ACTIVE, $l2->fresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->listingModerator->id,
            'action' => 'listing.bulk_approve',
        ]);
    }

    public function test_compliance_officer_can_approve_and_reject_18_plus_age_verification(): void
    {
        $candidateUser = User::factory()->create([
            'name' => 'Verification Candidate',
            'is_age_verified' => false,
        ]);

        $assurance = AgeAssurance::create([
            'user_id' => $candidateUser->id,
            'method' => 'government_id',
            'status' => 'pending',
            'declared_birthdate' => '1995-05-14',
        ]);

        // Approve
        Livewire::actingAs($this->complianceOfficer)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'verification'])
            ->call('approveAgeVerification', $assurance->id)
            ->assertHasNoErrors();

        $this->assertEquals('approved', $assurance->fresh()->status);
        $this->assertTrue($candidateUser->fresh()->is_age_verified);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->complianceOfficer->id,
            'action' => 'verification.approve',
            'target_id' => $assurance->id,
        ]);

        // Reject
        Livewire::actingAs($this->complianceOfficer)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'verification'])
            ->call('rejectAgeVerification', $assurance->id)
            ->assertHasNoErrors();

        $this->assertEquals('rejected', $assurance->fresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->complianceOfficer->id,
            'action' => 'verification.reject',
            'target_id' => $assurance->id,
        ]);
    }

    public function test_staff_can_resolve_content_report_with_notes(): void
    {
        $report = ContentReport::create([
            'reporter_id' => $this->regularUser->id,
            'reportable_type' => Listing::class,
            'reportable_id' => 1,
            'reason_code' => 'fraud_scam',
            'details' => 'Sender asking for wire transfers upfront.',
            'status' => 'open',
        ]);

        Livewire::actingAs($this->complianceOfficer)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'reports'])
            ->call('openResolveReportModal', $report->id)
            ->set('reportResolutionNotes', 'Listing reviewed and advertiser warned.')
            ->call('resolveReport', 'resolved')
            ->assertHasNoErrors();

        $this->assertEquals('resolved', $report->fresh()->status);
        $this->assertEquals('Listing reviewed and advertiser warned.', $report->fresh()->resolution_notes);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->complianceOfficer->id,
            'action' => 'report.resolved',
            'target_id' => $report->id,
        ]);
    }

    public function test_admin_can_inspect_filter_and_investigate_reports(): void
    {
        $report = ContentReport::create([
            'reporter_id' => $this->regularUser->id,
            'reportable_type' => Listing::class,
            'reportable_id' => 1,
            'reason_code' => 'underage_suspected',
            'details' => 'Advertiser profile claims high school senior year.',
            'status' => 'open',
            'risk_level' => 'critical',
            'is_urgent_takedown' => true,
        ]);

        Livewire::actingAs($this->complianceOfficer)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'reports'])
            ->assertSee('Reports & Incident Moderation')
            ->assertSee('Active Incident Queue')
            ->assertSee('Underage Suspected')
            ->call('openInspectReportModal', $report->id)
            ->assertSet('showInspectReportModal', true)
            ->call('investigateReport', $report->id);

        $this->assertEquals('investigating', $report->fresh()->status);

        // Test filter change
        Livewire::actingAs($this->complianceOfficer)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'reports'])
            ->set('reportStatusFilter', 'investigating')
            ->assertSee('Underage Suspected')
            ->set('reportStatusFilter', 'resolved')
            ->call('resetReportFilters')
            ->assertSet('reportStatusFilter', 'all');
    }

    public function test_finance_manager_can_issue_refund_creating_refund_record_and_audit_trail(): void
    {
        $txn = PaymentTransaction::create([
            'user_id' => $this->regularUser->id,
            'reference_id' => 'TXN_' . uniqid(),
            'amount_cents' => 4900,
            'currency' => 'USD',
            'status' => 'completed',
            'gateway' => 'stripe',
            'gateway_transaction_id' => 'ch_' . uniqid(),
        ]);

        Livewire::actingAs($this->financeManager)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('openRefundModal', $txn->id)
            ->set('refundReason', 'Customer requested accidental duplicate charge reversal.')
            ->call('issueRefund')
            ->assertHasNoErrors();

        $this->assertEquals('refunded', $txn->fresh()->status);
        $this->assertDatabaseHas('refunds', [
            'payment_transaction_id' => $txn->id,
            'amount_cents' => 4900,
            'reason' => 'Customer requested accidental duplicate charge reversal.',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->financeManager->id,
            'action' => 'billing.refund',
            'target_id' => $txn->id,
        ]);
    }

    public function test_finance_manager_cannot_refund_already_refunded_transaction(): void
    {
        $txn = PaymentTransaction::create([
            'user_id' => $this->regularUser->id,
            'reference_id' => 'TXN_' . uniqid(),
            'amount_cents' => 2500,
            'currency' => 'USD',
            'status' => 'refunded',
            'gateway' => 'stripe',
        ]);

        Livewire::actingAs($this->financeManager)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('openRefundModal', $txn->id)
            ->set('refundReason', 'Duplicate attempt.')
            ->call('issueRefund')
            ->assertHasErrors(['refundReason']);
    }

    public function test_seo_manager_can_create_and_update_cms_pages_and_seo_metas(): void
    {
        // 1. Create CMS Page
        Livewire::actingAs($this->seoManager)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'cms'])
            ->call('openCmsModal')
            ->set('cmsForm.title', 'Local Adult Classifieds Safety Guide')
            ->set('cmsForm.slug', 'safety-guide')
            ->set('cmsForm.content', '<p>Guidelines for safe meetings in public locations.</p>')
            ->set('cmsForm.is_published', true)
            ->call('saveCmsPage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'safety-guide',
            'title' => 'Local Adult Classifieds Safety Guide',
            'is_published' => true,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->seoManager->id,
            'action' => 'cms.create',
        ]);

        // 2. Create SEO Meta
        Livewire::actingAs($this->seoManager)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'seo'])
            ->call('openSeoModal')
            ->set('seoForm.page_route', 'locations.state')
            ->set('seoForm.meta_title', 'Browse Verified Adult Classifieds By State')
            ->set('seoForm.meta_description', 'Discover active local personal ads verified 18+ in your state.')
            ->set('seoForm.robots_directive', 'index, follow')
            ->call('saveSeo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('seo_metas', [
            'page_route' => 'locations.state',
            'meta_title' => 'Browse Verified Adult Classifieds By State',
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->seoManager->id,
            'action' => 'seo.create',
        ]);
    }

    public function test_super_admin_can_update_system_settings_with_audit_log(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'settings'])
            ->set('settingValues.default_search_radius_miles', '50')
            ->call('saveSystemSettings')
            ->assertHasNoErrors();

        $this->assertEquals('50', SystemSetting::where('key', 'default_search_radius_miles')->value('value'));
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->superAdmin->id,
            'action' => 'setting.update',
        ]);
    }

    public function test_super_admin_can_switch_settings_subtabs_and_send_test_email(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $component = Livewire::actingAs($this->superAdmin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'settings'])
            ->assertSee('Platform Identity &amp; Contact Metadata', false)
            ->call('setSettingsSubTab', 'branding')
            ->assertSee('Visual Assets, Logos &amp; Favicon Manager', false)
            ->call('setSettingsSubTab', 'smtp')
            ->assertSee('SMTP Mail Transport &amp; Delivery Server', false)
            ->call('setSettingsSubTab', 'notifications')
            ->assertSee('Real-Time Transactional Email Notification Matrix')
            ->assertSee('Email Verification on Signup')
            ->assertSee('Instant Email Alert on New Message');

        // Update settings in notifications
        $component->set('settingValues.mail_welcome_enabled', 'false')
            ->call('saveSystemSettings')
            ->assertHasNoErrors();

        $this->assertEquals('false', SystemSetting::where('key', 'mail_welcome_enabled')->value('value'));

        // Test sending test email
        $component->call('setSettingsSubTab', 'smtp')
            ->set('testEmailRecipient', 'test-smtp@example.com')
            ->call('sendTestEmail')
            ->assertHasNoErrors()
            ->assertSee('Test email sent successfully');

        $this->assertDatabaseHas('notifications_log', [
            'recipient_target' => 'test-smtp@example.com',
            'notification_type' => 'system.smtp_test',
            'status' => 'sent',
        ]);
    }

    public function test_non_super_admin_blocked_from_modifying_restricted_settings(): void
    {
        $adminUser = User::factory()->create([
            'name' => 'General Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        Livewire::actingAs($adminUser)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'settings'])
            ->set('settingValues.payment_gateway_mode', 'live')
            ->call('saveSystemSettings')
            ->assertStatus(403);
    }

    public function test_admin_can_inspect_audit_log_details(): void
    {
        $log = AdminAuditLog::create([
            'admin_user_id' => $this->superAdmin->id,
            'action' => 'security.audit_test',
            'target_type' => User::class,
            'target_id' => $this->regularUser->id,
            'before_state' => ['account_status' => 'active'],
            'after_state' => ['account_status' => 'suspended'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser Agent',
            'created_at' => now(),
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'audit'])
            ->call('inspectAuditLog', $log->id)
            ->assertSet('showAuditDetailModal', true)
            ->assertSet('inspectedAuditLogId', $log->id)
            ->assertSee('Audit Record #' . $log->id);
    }

    public function test_staff_can_create_and_toggle_advertisements(): void
    {
        Livewire::actingAs($this->financeManager)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'ads'])
            ->call('openAdModal')
            ->set('adForm.name', 'Safe Dating Verified Partner')
            ->set('adForm.slot', 'sidebar')
            ->set('adForm.advertiser_name', 'VerifiedPartner Co')
            ->set('adForm.is_active', true)
            ->call('saveAd')
            ->assertHasNoErrors();

        $ad = AdPlacement::where('name', 'Safe Dating Verified Partner')->first();
        $this->assertNotNull($ad);
        $this->assertTrue($ad->is_active);

        // Toggle ad
        Livewire::actingAs($this->financeManager)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'ads'])
            ->call('toggleAd', $ad->id)
            ->assertHasNoErrors();

        $this->assertFalse($ad->fresh()->is_active);
    }

    public function test_admin_can_switch_tabs_to_locations_and_access_catalog(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'dashboard'])
            ->call('setTab', 'locations')
            ->assertSet('tab', 'locations')
            ->assertSee('City, alias &amp; ZIP manager', false);
    }

    public function test_admin_dashboard_controller_handles_tab_query_parameter(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard', ['tab' => 'locations']));
        $response->assertStatus(200);
        $response->assertSee('Locations');

        $responsePayments = $this->actingAs($this->superAdmin)->get(route('admin.dashboard', ['tab' => 'payments']));
        $responsePayments->assertStatus(200);
        $responsePayments->assertSee('Payments');

        $responseSubscriptions = $this->actingAs($this->superAdmin)->get(route('admin.dashboard', ['tab' => 'subscriptions']));
        $responseSubscriptions->assertStatus(200);
        $responseSubscriptions->assertSee('Subscriptions');

        $responseVerification = $this->actingAs($this->superAdmin)->get(route('admin.dashboard', ['tab' => 'verification']));
        $responseVerification->assertStatus(200);

        $responseNotifications = $this->actingAs($this->superAdmin)->get(route('admin.dashboard', ['tab' => 'notifications']));
        $responseNotifications->assertStatus(200);
    }

    public function test_admin_can_switch_to_subscriptions_tab_in_livewire(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'subscriptions'])
            ->assertSet('tab', 'subscriptions')
            ->assertHasNoErrors()
            ->assertViewHas('assignableUsers');
    }

    public function test_admin_can_switch_to_payments_tab_in_livewire(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'dashboard'])
            ->call('setTab', 'payments')
            ->assertSet('tab', 'payments')
            ->assertHasNoErrors();
    }

    public function test_dashboard_realtime_filter_helpers_work_smoothly(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'dashboard'])
            ->call('setUserStatusFilter', 'active')
            ->assertSet('tab', 'users')
            ->assertSet('userStatusFilter', 'active')
            ->call('setListingStatusFilter', 'rejected')
            ->assertSet('tab', 'listings')
            ->assertSet('listingStatusFilter', 'rejected')
            ->call('setListingCategoryFilter', 1)
            ->assertSet('tab', 'listings')
            ->assertSet('listingCategoryFilter', 1)
            ->call('setDashboardLocationMode', 'states')
            ->assertSet('dashboardLocationMode', 'states');
    }
}
