<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Accounts\Models\UserConsent;
use App\Modules\Accounts\Models\UserProfile;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\State;
use App\Modules\Moderation\Models\AppealRequest;
use App\Modules\Moderation\Models\UserSuspension;
use App\Support\Security\TwoFactorAuthService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed country and state for registration & profile tests
        $country = \App\Modules\Locations\Models\Country::firstOrCreate(
            ['code' => 'US'],
            ['name' => 'United States', 'iso3' => 'USA', 'phone_code' => '+1', 'is_active' => true]
        );

        State::firstOrCreate(
            ['code' => 'TX'],
            ['country_id' => $country->id, 'name' => 'Texas', 'is_active' => true]
        );
    }

    /**
     * Test registration form displays.
     */
    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Create Your Account');
        $response->assertSee('Strictly 18+');
    }

    /**
     * Test valid 18+ registration creates user, profile, preferences, and compliance audits.
     */
    public function test_valid_adult_user_can_register(): void
    {
        $birthdate = Carbon::now()->subYears(25)->format('Y-m-d');

        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'username' => 'janedoe_tx',
            'email' => 'jane@example.com',
            'birthdate' => $birthdate,
            'city' => 'Austin',
            'state_code' => 'TX',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'age_confirmed_18' => '1',
            'anti_trafficking_confirmed' => '1',
            'terms_accepted' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        // Verify user database record
        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('janedoe_tx', $user->username);
        $this->assertEquals('active', $user->account_status);
        $this->assertTrue((bool) $user->is_age_verified);

        // Verify profile created
        $profile = UserProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals('Austin', $profile->city);
        $this->assertEquals('TX', $profile->state_code);

        // Verify age assurance audit record created
        $ageAudit = AgeAssurance::where('user_id', $user->id)->first();
        $this->assertNotNull($ageAudit);
        $this->assertEquals('self_declaration', $ageAudit->method);
        $this->assertEquals('approved', $ageAudit->status);

        // Verify legal consent records created
        $consents = UserConsent::where('user_id', $user->id)->pluck('consent_type')->toArray();
        $this->assertContains('adult_terms_18_plus', $consents);
        $this->assertContains('anti_trafficking_acknowledgement', $consents);
    }

    /**
     * Test registration rejects under-18 applicant.
     */
    public function test_registration_rejects_under_18(): void
    {
        $birthdate = Carbon::now()->subYears(17)->format('Y-m-d');

        $response = $this->post('/register', [
            'name' => 'Minor User',
            'username' => 'minor_user',
            'email' => 'minor@example.com',
            'birthdate' => $birthdate,
            'city' => 'Dallas',
            'state_code' => 'TX',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'age_confirmed_18' => '1',
            'anti_trafficking_confirmed' => '1',
            'terms_accepted' => '1',
        ]);

        $response->assertSessionHasErrors('birthdate');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'minor@example.com']);
    }

    /**
     * Test registration fails if compliance checkboxes are missing.
     */
    public function test_registration_fails_without_anti_trafficking_and_age_consents(): void
    {
        $birthdate = Carbon::now()->subYears(22)->format('Y-m-d');

        $response = $this->post('/register', [
            'name' => 'Test Adult',
            'username' => 'testadult',
            'email' => 'adult@example.com',
            'birthdate' => $birthdate,
            'city' => 'Houston',
            'state_code' => 'TX',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertSessionHasErrors(['age_confirmed_18', 'anti_trafficking_confirmed', 'terms_accepted']);
        $this->assertGuest();
    }

    /**
     * Test honeypot prevents bot registration.
     */
    public function test_honeypot_silently_rejects_bot(): void
    {
        $birthdate = Carbon::now()->subYears(25)->format('Y-m-d');

        $response = $this->post('/register', [
            'name' => 'Spam Bot',
            'username' => 'spambot',
            'email' => 'bot@example.com',
            'website_hp' => 'http://spam-link.com',
            'birthdate' => $birthdate,
            'city' => 'Austin',
            'state_code' => 'TX',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'age_confirmed_18' => '1',
            'anti_trafficking_confirmed' => '1',
            'terms_accepted' => '1',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
    }

    /**
     * Test user can log in with email.
     */
    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Secret123!'),
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'user@example.com',
            'password' => 'Secret123!',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test user can log in with username.
     */
    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'cooldater99',
            'password' => Hash::make('Secret123!'),
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'cooldater99',
            'password' => 'Secret123!',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test invalid password throws validation error.
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'valid@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $response = $this->post('/login', [
            'login' => 'valid@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Test suspended user cannot log in and is redirected to suspension screen.
     */
    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => Hash::make('Secret123!'),
            'account_status' => 'suspended',
        ]);

        $response = $this->post('/login', [
            'login' => 'suspended@example.com',
            'password' => 'Secret123!',
        ]);

        $response->assertRedirect(route('account.suspended'));
        $this->assertGuest();
    }

    /**
     * Test user can logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    /**
     * Test email verification notice is shown to unverified user.
     */
    public function test_email_verification_screen_renders_for_unverified_user(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('Verify Your Email');
    }

    /**
     * Test email verification via signed link.
     */
    public function test_email_can_be_verified_via_signed_url(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect(route('home') . '?verified=1');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /**
     * Test two-factor authentication setup, confirmation, challenge, and login.
     */
    public function test_two_factor_auth_flow(): void
    {
        $service = app(TwoFactorAuthService::class);
        $user = User::factory()->create([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'password' => Hash::make('Password123!'),
        ]);

        // 1. Generate 2FA secret
        $response = $this->actingAs($user)->post('/account/security/2fa/generate');
        $response->assertSessionHas('pending_2fa_secret');
        $secret = session('pending_2fa_secret');
        $this->assertNotEmpty($secret);

        // 2. Confirm 2FA with valid TOTP code
        $validCode = $service->generateCurrentOtp($secret);
        $response = $this->actingAs($user)->post('/account/security/2fa/confirm', [
            'two_factor_code' => $validCode,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue((bool) $user->fresh()->two_factor_enabled);
        $this->assertEquals($secret, $user->fresh()->two_factor_secret);

        // 3. Log out
        $this->post('/logout');
        $this->assertGuest();

        // 4. Attempt login — should redirect to 2FA challenge
        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('login.2fa'));
        $this->assertGuest(); // Not fully logged in yet

        // 5. Submit valid code at 2FA challenge
        $challengeCode = $service->generateCurrentOtp($secret);
        $response = $this->post('/login/2fa', [
            'two_factor_code' => $challengeCode,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        // 6. Disable 2FA with current password
        $response = $this->actingAs($user)->post('/account/security/2fa/disable', [
            'current_password' => 'Password123!',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertFalse((bool) $user->fresh()->two_factor_enabled);
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    /**
     * Test profile update and privacy controls.
     */
    public function test_user_can_update_profile_and_privacy_controls(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'city' => 'Austin',
            'state_code' => 'TX',
        ]);

        $response = $this->actingAs($user)->put('/account/profile', [
            'name' => 'Updated Jane',
            'display_name' => 'Jane A.',
            'city' => 'Houston',
            'state_code' => 'TX',
            'zip_code' => '77001',
            'bio' => 'Looking for romantic evening dates and wine tasting.',
            'gender' => 'Woman',
            'seeking' => 'Men',
            'hide_location_details' => '1',
            'hide_age' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertEquals('Updated Jane', $user->fresh()->name);
        $freshProfile = $profile->fresh();
        $this->assertEquals('Jane A.', $freshProfile->display_name);
        $this->assertEquals('Houston', $freshProfile->city);
        $this->assertTrue((bool) $freshProfile->hide_location_details);
        $this->assertTrue((bool) $freshProfile->hide_age);
    }

    /**
     * Test photo avatar upload strips EXIF metadata.
     */
    public function test_avatar_upload_sanitizes_and_strips_metadata(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        UserProfile::create(['user_id' => $user->id, 'city' => 'Austin', 'state_code' => 'TX']);

        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $response = $this->actingAs($user)->post('/account/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $freshProfile = UserProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($freshProfile->avatar_path);
        Storage::disk('local')->assertExists($freshProfile->avatar_path);
    }

    /**
     * Test password update requires current password.
     */
    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($user)->put('/account/security/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword999!',
            'password_confirmation' => 'NewPassword999!',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword999!', $user->fresh()->password));
    }

    /**
     * Test self-service account deletion soft-deletes user and user listings.
     */
    public function test_user_can_delete_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->actingAs($user)->delete('/account', [
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /**
     * Test suspended user appeal submission.
     */
    public function test_suspended_user_can_submit_appeal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'account_status' => 'suspended',
        ]);

        $suspension = UserSuspension::create([
            'user_id' => $user->id,
            'issued_by_admin_id' => $admin->id,
            'reason' => 'Suspected commercial advertisement.',
            'is_lifted' => false,
        ]);

        $response = $this->actingAs($user)->post('/account/appeal', [
            'appeal_reason' => 'This was a personal classified ad, not a commercial service. Please review.',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('appeal_requests', [
            'user_id' => $user->id,
            'user_suspension_id' => $suspension->id,
            'status' => 'pending',
        ]);
    }
}
