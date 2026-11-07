<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Moderation\DTOs\ReportData;
use App\Modules\Moderation\Models\ContentReport;
use App\Modules\Moderation\Models\DmcaRequest;
use App\Modules\Moderation\Models\EvidencePreservation;
use App\Modules\Moderation\Models\LegalRequest;
use App\Modules\Moderation\Models\StateCompliance;
use App\Modules\Moderation\Services\AccountRiskService;
use App\Modules\Moderation\Services\ComplianceService;
use App\Modules\Moderation\Services\DuplicateDetectionService;
use App\Modules\Moderation\Services\ModerationService;
use App\Modules\Moderation\Services\TextScreeningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrustSafetyAndComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $complianceOfficer;
    protected User $regularUser;
    protected User $targetUser;
    protected Category $category;
    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->category = Category::first();
        $this->city = City::first();

        $this->superAdmin = User::factory()->create([
            'name' => 'Chief Compliance Officer',
            'email' => 'cco@example.com',
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $this->complianceOfficer = User::factory()->create([
            'name' => 'Safety Lead',
            'email' => 'safety@example.com',
            'role' => 'compliance_officer',
            'account_status' => 'active',
        ]);

        $this->regularUser = User::factory()->create([
            'name' => 'Alice Member',
            'email' => 'alice@example.com',
            'account_status' => 'active',
            'is_age_verified' => false,
        ]);

        $this->targetUser = User::factory()->create([
            'name' => 'Bob Subject',
            'email' => 'bob@example.com',
            'account_status' => 'active',
            'is_age_verified' => false,
        ]);
    }

    public function test_text_screening_flags_commercial_solicitation_and_prostitution(): void
    {
        $scanner = app(TextScreeningService::class);

        $result = $scanner->screen('Available for discreet escort service with hourly rate and generous ppm donation required.');

        $this->assertEquals(TextScreeningService::RISK_CRITICAL, $result['risk_level']);
        $this->assertGreaterThanOrEqual(80, $result['score']);
        $this->assertContains('prohibited_solicitation_keyword: [escort service]', $result['flags']);
    }

    public function test_text_screening_flags_trafficking_and_coercion_indicators(): void
    {
        $scanner = app(TextScreeningService::class);

        $result = $scanner->screen('Fresh shipment of new girls in town, strictly monitored by driver.');

        $this->assertEquals(TextScreeningService::RISK_CRITICAL, $result['risk_level']);
        $this->assertEquals('auto_suspend', $result['action']);
        $this->assertContains('critical_trafficking_phrase: [new girls in town]', $result['flags']);
    }

    public function test_text_screening_flags_underage_references(): void
    {
        $scanner = app(TextScreeningService::class);

        $result = $scanner->screen('Schoolgirl style, barely 18 look.');

        $this->assertEquals(TextScreeningService::RISK_CRITICAL, $result['risk_level']);
        $this->assertGreaterThanOrEqual(95, $result['score']);
        $this->assertContains('critical_underage_reference: [schoolgirl]', $result['flags']);
    }

    public function test_duplicate_content_detection_flags_identical_and_similar_listings(): void
    {
        $detector = app(DuplicateDetectionService::class);

        $title = 'Looking for coffee and jazz lover';
        $desc = 'Independent adult looking for coffee partner on weekends in downtown.';

        // Create base listing
        $base = Listing::create([
            'user_id' => $this->regularUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => $title,
            'slug' => 'base-listing-' . uniqid(),
            'description' => $desc,
            'content_hash' => $detector->generateContentHash($title, $desc),
            'poster_age' => 28,
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        // Exact match
        $checkExact = $detector->check($title, $desc);
        $this->assertTrue($checkExact['is_duplicate']);
        $this->assertEquals($base->id, $checkExact['match_listing_id']);
        $this->assertEquals(100.0, $checkExact['similarity']);

        // Completely unique
        $checkUnique = $detector->check('Entirely different topic', 'No overlapping wording whatsoever here.');
        $this->assertFalse($checkUnique['is_duplicate']);
    }

    public function test_account_risk_evaluation_calculates_risk_score_and_flags(): void
    {
        $riskService = app(AccountRiskService::class);

        // Account with rapid burst posting
        for ($i = 0; $i < 4; $i++) {
            Listing::create([
                'user_id' => $this->targetUser->id,
                'category_id' => $this->category->id,
                'state_id' => $this->city->state_id,
                'city_id' => $this->city->id,
                'title' => "Burst Listing {$i}",
                'slug' => "burst-listing-{$i}-" . uniqid(),
                'description' => "Description content for burst {$i}",
                'content_hash' => hash('sha256', "burst_{$i}"),
                'poster_age' => 25,
                'intent' => 'dating',
                'status' => Listing::STATUS_ACTIVE,
            ]);
        }

        $evaluation = $riskService->evaluate($this->targetUser);

        $this->assertGreaterThan(30, $evaluation['score']);
        $this->assertContains('unverified_age_assurance', $evaluation['flags']);
        $this->assertTrue(collect($evaluation['flags'])->contains(fn ($f) => str_contains($f, 'rapid_posting_velocity')));
    }

    public function test_state_compliance_engine_enforces_mandatory_digital_id_states(): void
    {
        $compliance = app(ComplianceService::class);

        // Texas requires digital ID check
        $txCheckUnverified = $compliance->checkStateCompliance('TX', $this->regularUser);
        $this->assertFalse($txCheckUnverified['allowed']);
        $this->assertTrue($txCheckUnverified['requires_id_check']);
        $this->assertStringContainsString('TX HB 1181', $txCheckUnverified['legal_ref']);

        // When age verified, user is allowed
        $this->regularUser->is_age_verified = true;
        $this->regularUser->save();

        $txCheckVerified = $compliance->checkStateCompliance('TX', $this->regularUser);
        $this->assertTrue($txCheckVerified['allowed']);

        // California standard age gate (no mandatory 3P ID check required)
        $this->regularUser->is_age_verified = false;
        $this->regularUser->save();
        $caCheck = $compliance->checkStateCompliance('CA', $this->regularUser);
        $this->assertTrue($caCheck['allowed']);
        $this->assertFalse($caCheck['requires_id_check']);
    }

    public function test_urgent_report_for_underage_or_non_consensual_media_triggers_immediate_quarantine(): void
    {
        $modService = app(ModerationService::class);

        $listing = Listing::create([
            'user_id' => $this->targetUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Suspicious Listing Target',
            'slug' => 'suspicious-listing-' . uniqid(),
            'description' => 'Target listing under scrutiny.',
            'content_hash' => hash('sha256', 'target_' . uniqid()),
            'poster_age' => 24,
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        $reportResult = $modService->submitReport(new ReportData(
            reporterId: $this->regularUser->id,
            reportedUserId: $this->targetUser->id,
            reason: 'underage_suspected',
            details: 'Poster appears to be a minor without legal age capacity.',
            listingId: $listing->id
        ));

        $this->assertEquals('critical', $reportResult['risk_level']);
        // Verify listing was instantly quarantined from public active status
        $this->assertEquals(Listing::STATUS_SUSPENDED, $listing->fresh()->status);
        $this->assertStringContainsString('Quarantined for compliance review', $listing->fresh()->rejection_reason);
    }

    public function test_public_dmca_notice_submission_creates_record_and_quarantines_content(): void
    {
        $listing = Listing::create([
            'user_id' => $this->targetUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Copyrighted Photo Ad',
            'slug' => 'copyright-ad-' . uniqid(),
            'description' => 'Personal ad with stolen photographs.',
            'content_hash' => hash('sha256', 'photo_' . uniqid()),
            'poster_age' => 26,
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        $response = $this->post(route('dmca.submit'), [
            'claimant_name' => 'Jane Photographer',
            'claimant_email' => 'jane@photoagency.com',
            'claimant_phone' => '555-019-2834',
            'organization' => 'Photo Agency LLC',
            'copyrighted_work' => 'Original copyrighted portrait photography registered with US Copyright Office.',
            'infringing_url' => url("/listings/{$listing->slug}"),
            'target_listing_id' => $listing->id,
            'good_faith_attested' => '1',
            'perjury_attested' => '1',
            'digital_signature' => 'Jane Photographer',
        ]);

        $response->assertRedirect(route('dmca.show'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dmca_requests', [
            'claimant_email' => 'jane@photoagency.com',
            'digital_signature' => 'Jane Photographer',
            'status' => DmcaRequest::STATUS_PENDING,
        ]);

        // Target listing quarantined immediately
        $this->assertEquals(Listing::STATUS_SUSPENDED, $listing->fresh()->status);
    }

    public function test_admin_can_action_dmca_request_and_issue_takedown(): void
    {
        $listing = Listing::create([
            'user_id' => $this->targetUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Alleged Infringing Material',
            'slug' => 'alleged-infringing-' . uniqid(),
            'description' => 'Ad with challenged media.',
            'content_hash' => hash('sha256', 'alleged_' . uniqid()),
            'poster_age' => 27,
            'intent' => 'dating',
            'status' => Listing::STATUS_SUSPENDED,
        ]);

        $dmca = DmcaRequest::create([
            'ticket_number' => 'DMCA-' . uniqid(),
            'claimant_name' => 'Studio Owner',
            'claimant_email' => 'legal@studio.com',
            'copyrighted_work' => 'Registered images.',
            'infringing_url' => url('/listings/alleged-infringing'),
            'target_listing_id' => $listing->id,
            'digital_signature' => 'Studio Owner',
            'status' => DmcaRequest::STATUS_PENDING,
        ]);

        Livewire::actingAs($this->complianceOfficer)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'dmca'])
            ->call('openDmcaModal', $dmca->id, 'takedown_issued')
            ->set('dmcaNotes', 'Verified copyright registration certificate provided.')
            ->call('processDmca')
            ->assertHasNoErrors();

        $this->assertEquals(DmcaRequest::STATUS_TAKEDOWN_ISSUED, $dmca->fresh()->status);
        $this->assertEquals(Listing::STATUS_REJECTED, $listing->fresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->complianceOfficer->id,
            'action' => 'dmca.action',
        ]);
    }

    public function test_admin_can_fulfill_law_enforcement_request_and_freeze_evidence(): void
    {
        $listing = Listing::create([
            'user_id' => $this->targetUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Subject Listing Under Subpoena',
            'slug' => 'subpoena-subject-' . uniqid(),
            'description' => 'Evidence listing.',
            'content_hash' => hash('sha256', 'subpoena_' . uniqid()),
            'poster_age' => 29,
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        $legal = LegalRequest::create([
            'reference_code' => 'LE-2026-0881',
            'agency_name' => 'Federal Bureau of Investigation',
            'officer_name' => 'Special Agent Miller',
            'official_email' => 'miller@fbi.gov',
            'request_type' => LegalRequest::TYPE_2703F_PRESERVATION,
            'case_number' => 'CR-2026-9921',
            'target_user_id' => $this->targetUser->id,
            'target_listing_id' => $listing->id,
            'description' => 'Statutory preservation letter for all IP logs and communications.',
            'status' => LegalRequest::STATUS_RECEIVED,
        ]);

        // Freeze evidence
        Livewire::actingAs($this->complianceOfficer)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'legal'])
            ->call('freezeLegalEvidence', $legal->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('evidence_preservations', [
            'legal_request_id' => $legal->id,
            'preservable_type' => Listing::class,
            'preservable_id' => $listing->id,
            'is_active' => true,
        ]);

        // Fulfill legal request
        Livewire::actingAs($this->complianceOfficer)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'legal'])
            ->call('openLegalModal', $legal->id)
            ->set('legalStatusAction', 'fulfilled')
            ->set('legalComplianceNotes', 'Produced preservation snapshot pursuant to 18 U.S.C. 2703(f).')
            ->call('processLegalRequest')
            ->assertHasNoErrors();

        $this->assertEquals(LegalRequest::STATUS_FULFILLED, $legal->fresh()->status);
        $this->assertNotNull($legal->fresh()->fulfilled_at);
    }

    public function test_privacy_deletion_is_blocked_when_account_is_under_active_evidence_hold(): void
    {
        $compliance = app(ComplianceService::class);

        // User without hold can erase
        $canEraseInitial = $compliance->canEraseUserData($this->targetUser);
        $this->assertTrue($canEraseInitial['can_erase']);

        // Place statutory evidence hold on user
        EvidencePreservation::create([
            'preservable_type' => User::class,
            'preservable_id' => $this->targetUser->id,
            'hold_reason' => 'Active federal subpoena hold',
            'snapshot_data' => $this->targetUser->toArray(),
            'is_active' => true,
            'frozen_by_admin_id' => $this->superAdmin->id,
        ]);

        // Attempting erasure is now blocked
        $canEraseBlocked = $compliance->canEraseUserData($this->targetUser);
        $this->assertFalse($canEraseBlocked['can_erase']);
        $this->assertStringContainsString('preservation order', $canEraseBlocked['reason']);
    }

    public function test_public_user_can_submit_user_abuse_report(): void
    {
        $response = $this->actingAs($this->regularUser)->post(
            route('users.report.submit', $this->targetUser->id),
            [
                'reason' => 'harassment',
                'details' => 'User sent threatening repeated messages despite being requested to cease contact.',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('content_reports', [
            'reporter_id' => $this->regularUser->id,
            'reportable_type' => User::class,
            'reportable_id' => $this->targetUser->id,
            'reason_code' => 'harassment',
            'status' => 'open',
        ]);
    }

    public function test_admin_can_toggle_state_id_verification_mandate(): void
    {
        $st = StateCompliance::where('state_code', 'TX')->first();
        $this->assertNotNull($st);
        $initialState = $st->requires_id_verification;

        Livewire::actingAs($this->superAdmin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'compliance'])
            ->call('toggleStateIdRequirement', $st->id)
            ->assertHasNoErrors();

        $this->assertEquals(!$initialState, $st->fresh()->requires_id_verification);
    }

    public function test_evidence_hold_release_workflow(): void
    {
        $hold = EvidencePreservation::create([
            'preservable_type' => User::class,
            'preservable_id' => $this->targetUser->id,
            'hold_reason' => 'Temporary investigation hold',
            'snapshot_data' => $this->targetUser->toArray(),
            'is_active' => true,
            'frozen_by_admin_id' => $this->superAdmin->id,
        ]);

        Livewire::actingAs($this->complianceOfficer)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'evidence'])
            ->call('releaseEvidenceHold', $hold->id)
            ->assertHasNoErrors();

        $this->assertFalse($hold->fresh()->is_active);

        // User is now eligible for privacy erasure
        $canErase = app(ComplianceService::class)->canEraseUserData($this->targetUser);
        $this->assertTrue($canErase['can_erase']);
    }
}
