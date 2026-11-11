<?php

namespace Tests\Feature;

use App\Livewire\AccountDashboard;
use App\Livewire\Admin\AdminEnterpriseHub;
use App\Livewire\Messenger\MessengerHub;
use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Verification\Models\IdentityVerification;
use App\Modules\Verification\Services\IdentityVerificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StrictIdentityVerificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    protected function createListingFor(User $user, array $overrides = []): Listing
    {
        $category = Category::where('slug', 'women-seeking-men')->first() ?? Category::first();
        $city = City::first();
        $state = $city?->state ?? State::first();

        return Listing::create(array_merge([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'title' => 'Authentic Connection Listing ' . uniqid(),
            'slug' => 'authentic-listing-' . uniqid(),
            'description' => 'Detailed personal classified ad text exceeding twenty characters for validation.',
            'poster_age' => 28,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ], $overrides));
    }

    public function test_account_activation_email_phone_age_and_admin_role_never_grant_blue_badge(): void
    {
        $regularActiveUser = User::factory()->create([
            'name' => 'Regular Active Member',
            'role' => 'user',
            'account_status' => 'active',
            'email_verified_at' => now(),
            'phone' => '+15125550100',
            'phone_verified_at' => now(),
            'is_age_verified' => true,
        ]);

        $adminWithoutId = User::factory()->create([
            'name' => 'System Administrator',
            'role' => 'admin',
            'account_status' => 'active',
            'email_verified_at' => now(),
            'is_age_verified' => true,
        ]);

        $superAdminWithoutId = User::factory()->create([
            'name' => 'Root Super Admin',
            'role' => 'super_admin',
            'account_status' => 'active',
            'email_verified_at' => now(),
            'is_age_verified' => true,
        ]);

        foreach ([$regularActiveUser, $adminWithoutId, $superAdminWithoutId] as $account) {
            $this->assertFalse($account->hasVerifiedIdentity());
            $this->assertFalse((bool) $account->is_identity_verified);
            $this->assertSame(IdentityVerification::STATUS_NOT_SUBMITTED, $account->identityVerificationStatus());
        }

        // Admin profile header still shows Status: Active, but NEVER the Blue Verified Badge
        $this->actingAs($adminWithoutId);
        Livewire::test(AccountDashboard::class)
            ->assertSee('System Administrator')
            ->assertSee('Active')
            ->assertSee('Verify ID')
            ->assertDontSee('Blue Verified Badge — Identity Verified by Admin');

        // Listing created by unverified admin displays Member Photo Gallery without Verified badge
        $adminListing = $this->createListingFor($adminWithoutId);
        $this->get(route('listings.show', $adminListing->slug))
            ->assertStatus(200)
            ->assertSee('Member Photo Gallery')
            ->assertSee('Profile Photo Preview')
            ->assertSee('Identity Not Yet Verified')
            ->assertDontSee('Blue Verified Identity Badge')
            ->assertDontSee('Verified Profile Photo');

        // API status endpoint reflects strict not_submitted state
        $this->actingAs($adminWithoutId)
            ->getJson(route('api.account.verification.status'))
            ->assertStatus(200)
            ->assertJson([
                'user_id' => $adminWithoutId->id,
                'is_identity_verified' => false,
                'status' => IdentityVerification::STATUS_NOT_SUBMITTED,
                'resubmit_allowed' => true,
            ]);
    }

    public function test_all_eight_verification_states_enforced_strictly_across_lifecycle(): void
    {
        $user = User::factory()->create(['name' => 'Verifiable Member', 'account_status' => 'active']);
        $adminReviewer = User::factory()->create(['name' => 'Compliance Officer', 'role' => 'admin', 'account_status' => 'active']);
        $listing = $this->createListingFor($user);

        // 1. Submit ID documents -> pending (NO badge)
        $this->actingAs($user)
            ->post(route('account.verification.store'), [
                'document_type' => 'drivers_license',
                'document_front' => UploadedFile::fake()->image('id-front.jpg', 900, 600),
                'document_back' => UploadedFile::fake()->image('id-back.jpg', 900, 600),
                'selfie' => UploadedFile::fake()->image('selfie.jpg', 700, 700),
            ])
            ->assertRedirect(route('account.verification.show'));

        $verification = IdentityVerification::where('user_id', $user->id)->latest('id')->firstOrFail();
        $user->refresh();
        $this->assertSame(IdentityVerification::STATUS_PENDING, $user->identityVerificationStatus());
        $this->assertFalse($user->hasVerifiedIdentity());
        $this->assertFalse($listing->fresh()->is_verified_poster);

        Livewire::actingAs($user)->test(AccountDashboard::class)
            ->assertSee('ID Pending Review')
            ->assertDontSee('Blue Verified Badge — Identity Verified by Admin');

        // 2. Mark under_review -> NO badge yet
        $this->actingAs($adminReviewer)
            ->post(route('admin.verifications.under-review', $verification))
            ->assertRedirect();
        $user->refresh();
        $this->assertSame(IdentityVerification::STATUS_UNDER_REVIEW, $user->identityVerificationStatus());
        $this->assertFalse($user->hasVerifiedIdentity());

        // 3. Request resubmission -> NO badge
        $this->actingAs($adminReviewer)
            ->post(route('admin.verifications.resubmit', $verification), [
                'reason' => 'Edges cropped on ID front',
            ])
            ->assertRedirect();
        $user->refresh();
        $this->assertSame(IdentityVerification::STATUS_RESUBMISSION_REQUIRED, $user->identityVerificationStatus());
        $this->assertFalse($user->hasVerifiedIdentity());

        // 4. Reject -> NO badge
        $this->actingAs($adminReviewer)
            ->post(route('admin.verifications.reject', $verification), [
                'rejection_reason' => 'Glare obscured photo ID',
            ])
            ->assertRedirect();
        $user->refresh();
        $this->assertSame(IdentityVerification::STATUS_REJECTED, $user->identityVerificationStatus());
        $this->assertFalse($user->hasVerifiedIdentity());

        // 5. Resubmit and approve -> Blue Verified Badge activates everywhere!
        $this->actingAs($user)
            ->post(route('account.verification.store'), [
                'document_type' => 'passport',
                'document_front' => UploadedFile::fake()->image('passport.jpg', 900, 600),
                'selfie' => UploadedFile::fake()->image('clear-selfie.jpg', 700, 700),
            ])
            ->assertRedirect();

        $approvedRecord = IdentityVerification::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->actingAs($adminReviewer)
            ->post(route('admin.verifications.approve', $approvedRecord), [
                'admin_notes' => 'Passport and selfie verified.',
            ])
            ->assertRedirect(route('admin.verifications.index'));

        $user->refresh();
        $this->assertTrue($user->hasVerifiedIdentity());
        $this->assertTrue((bool) $user->is_identity_verified);
        $this->assertSame(IdentityVerification::STATUS_APPROVED, $user->identityVerificationStatus());
        $this->assertTrue($listing->fresh()->is_verified_poster);

        // Verified user sees Blue Verified Badge in dashboard, gallery, search API, and messenger
        Livewire::actingAs($user)->test(AccountDashboard::class)
            ->assertSee('Blue Verified Badge — Identity Verified by Admin');

        $this->get(route('listings.show', $listing->slug))
            ->assertStatus(200)
            ->assertSee('Verified Member Photo Gallery')
            ->assertSee('Blue Verified Identity Badge');

        $this->getJson('/api/listings/search?verified_only=1')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);

        // 6. Revoke -> Blue Verified Badge immediately stripped everywhere (cache cleared)
        $this->actingAs($adminReviewer)
            ->post(route('admin.verifications.revoke', $approvedRecord), [
                'reason' => 'Compliance review triggered revocation.',
            ])
            ->assertRedirect(route('admin.verifications.index'));

        $user->refresh();
        $this->assertFalse($user->hasVerifiedIdentity());
        $this->assertFalse((bool) $user->is_identity_verified);
        $this->assertSame(IdentityVerification::STATUS_REVOKED, $user->identityVerificationStatus());
        $this->assertFalse($listing->fresh()->is_verified_poster);

        Livewire::actingAs($user)->test(AccountDashboard::class)
            ->assertSee('Verification Revoked')
            ->assertDontSee('Blue Verified Badge — Identity Verified by Admin');

        $this->get(route('listings.show', $listing->slug))
            ->assertStatus(200)
            ->assertSee('Identity Not Yet Verified')
            ->assertDontSee('Blue Verified Identity Badge');

        // 7. Expired verification -> Blue Verified Badge disappears automatically
        $expiredRecord = IdentityVerification::create([
            'user_id' => $user->id,
            'document_type' => 'passport',
            'document_front_path' => 'verifications/p2.jpg',
            'selfie_path' => 'verifications/s2.jpg',
            'status' => IdentityVerification::STATUS_APPROVED,
            'reviewer_id' => $adminReviewer->id,
            'submitted_at' => now()->subYear(),
            'reviewed_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);
        User::clearVerificationCache($user->id);
        $user->refresh();

        $this->assertSame(IdentityVerification::STATUS_EXPIRED, $user->identityVerificationStatus());
        $this->assertFalse($user->hasVerifiedIdentity());
        $this->assertFalse($listing->fresh()->is_verified_poster);
    }

    public function test_messenger_displays_verified_badge_only_when_peer_has_valid_approved_id(): void
    {
        $viewer = User::factory()->create(['account_status' => 'active']);
        $unverifiedPeer = User::factory()->create(['name' => 'Unverified Peer', 'account_status' => 'active']);
        $verifiedPeer = User::factory()->create(['name' => 'Verified Peer', 'account_status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);

        IdentityVerification::create([
            'user_id' => $verifiedPeer->id,
            'document_type' => 'state_id',
            'document_front_path' => 'verifications/front.jpg',
            'selfie_path' => 'verifications/selfie.jpg',
            'status' => IdentityVerification::STATUS_VERIFIED,
            'reviewer_id' => $admin->id,
            'submitted_at' => now()->subHour(),
            'reviewed_at' => now(),
        ]);

        ContactMessage::create([
            'sender_id' => $unverifiedPeer->id,
            'recipient_id' => $viewer->id,
            'sender_name' => $unverifiedPeer->name,
            'sender_email_encrypted' => $unverifiedPeer->email,
            'message_encrypted' => 'Hello from unverified peer',
            'client_ip' => '127.0.0.1',
        ]);

        Livewire::actingAs($viewer)
            ->test(MessengerHub::class)
            ->assertSee('Unverified Peer')
            ->assertDontSee('title="Verified Member"', false);

        ContactMessage::create([
            'sender_id' => $verifiedPeer->id,
            'recipient_id' => $viewer->id,
            'sender_name' => $verifiedPeer->name,
            'sender_email_encrypted' => $verifiedPeer->email,
            'message_encrypted' => 'Hello from ID-verified peer',
            'client_ip' => '127.0.0.1',
        ]);

        Livewire::actingAs($viewer)
            ->test(MessengerHub::class)
            ->assertSee('Verified Peer')
            ->assertSee('title="Verified Member"', false);
    }

    public function test_tampering_mass_assignment_and_unauthorized_admin_approval_are_blocked(): void
    {
        $user = User::factory()->create(['name' => 'Sneaky User', 'account_status' => 'active']);
        $nonAdmin = User::factory()->create(['role' => 'user', 'account_status' => 'active']);
        $listing = $this->createListingFor($user);

        // Attempt 1: Mass-assignment via fill()
        $user->fill(['is_identity_verified' => true])->save();
        $this->assertFalse((bool) $user->fresh()->is_identity_verified);

        // Attempt 2: Direct SQL manipulation without IdentityVerification row
        DB::table('users')->where('id', $user->id)->update(['is_identity_verified' => 1]);
        DB::table('listings')->where('id', $listing->id)->update(['is_verified_poster' => 1]);
        User::clearVerificationCache($user->id);

        $this->assertFalse($user->fresh()->hasVerifiedIdentity());
        $this->assertFalse((bool) $user->fresh()->is_identity_verified);
        $this->assertFalse($listing->fresh()->is_verified_poster);

        // Search filter verified_only must STILL exclude tampered row
        $this->getJson('/api/listings/search?verified_only=1')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);

        // Audit command cleans stale DB booleans back to 0 non-destructively
        $stats = app(IdentityVerificationService::class)->auditAllAccounts(true);
        $this->assertGreaterThanOrEqual(1, $stats['incorrect_user_flags_cleared']);
        $this->assertSame(0, (int) DB::table('users')->where('id', $user->id)->value('is_identity_verified'));
        $this->assertSame(0, (int) DB::table('listings')->where('id', $listing->id)->value('is_verified_poster'));

        // Attempt 3: Non-admin blocked on verification routes and service approval
        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'document_type' => 'passport',
            'document_front_path' => 'verifications/p.jpg',
            'selfie_path' => 'verifications/s.jpg',
            'status' => IdentityVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->actingAs($nonAdmin)
            ->post(route('admin.verifications.approve', $verification))
            ->assertStatus(403);

        $this->expectException(AuthorizationException::class);
        app(IdentityVerificationService::class)->approve($verification, $nonAdmin);
    }

    public function test_admin_user_editor_cannot_bypass_id_verification_via_checkbox(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        $target = User::factory()->create(['name' => 'Target User', 'account_status' => 'active']);
        $this->createListingFor($target);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class)
            ->call('openEditUserModal', $target->id)
            ->set('editUserIsAgeVerified', true)
            ->set('editUserIsIdentityVerified', true)
            ->call('updateUser');

        $target->refresh();
        $this->assertTrue((bool) $target->is_age_verified);
        $this->assertFalse($target->hasVerifiedIdentity());
        $this->assertSame(0, (int) DB::table('users')->where('id', $target->id)->value('is_identity_verified'));
    }
}
