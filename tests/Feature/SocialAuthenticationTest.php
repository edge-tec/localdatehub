<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_google_redirect_gracefully_fails_when_unconfigured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $response = $this->get(route('social.redirect', 'google'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_unsupported_provider_redirect_returns_error(): void
    {
        $response = $this->get(route('social.redirect', 'twitter'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Unsupported authentication provider.');
    }

    public function test_google_redirect_initiates_oauth_when_configured(): void
    {
        config([
            'services.google.client_id' => 'test-google-client-id.apps.googleusercontent.com',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $response = $this->get(route('social.redirect', 'google'));

        // Socialite redirects to accounts.google.com
        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_google_callback_creates_and_authenticates_new_user(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
        ]);

        $mockSocialUser = new SocialiteUser();
        $mockSocialUser->id = 'google-uid-123456';
        $mockSocialUser->name = 'Jane Doe';
        $mockSocialUser->email = 'jane.doe@example.com';
        $mockSocialUser->avatar = 'https://lh3.googleusercontent.com/photo.jpg';

        $mockProvider = Mockery::mock(SocialiteProvider::class);
        $mockProvider->shouldReceive('user')->andReturn($mockSocialUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($mockProvider);

        $response = $this->get(route('social.callback', 'google'));

        $response->assertRedirect(route('account.dashboard'));
        $response->assertSessionHas('success');

        $this->assertAuthenticated();

        $user = User::where('email', 'jane.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('google-uid-123456', $user->google_id);
        $this->assertEquals('google', $user->oauth_provider);
        $this->assertEquals('google-uid-123456', $user->oauth_id);
        $this->assertNotNull($user->email_verified_at);

        // Verify profile and relations exist
        $this->assertNotNull($user->profile);
        $this->assertNotNull($user->preferences);
    }

    public function test_google_callback_authenticates_and_links_existing_user(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
        ]);

        $existingUser = User::factory()->create([
            'email' => 'existing.member@example.com',
            'name' => 'Existing Member',
            'google_id' => null,
            'oauth_provider' => null,
        ]);

        $mockSocialUser = new SocialiteUser();
        $mockSocialUser->id = 'google-uid-789012';
        $mockSocialUser->name = 'Existing Member';
        $mockSocialUser->email = 'existing.member@example.com';
        $mockSocialUser->avatar = 'https://lh3.googleusercontent.com/avatar.jpg';

        $mockProvider = Mockery::mock(SocialiteProvider::class);
        $mockProvider->shouldReceive('user')->andReturn($mockSocialUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($mockProvider);

        $response = $this->get(route('social.callback', 'google'));

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($existingUser);

        $existingUser->refresh();
        $this->assertEquals('google-uid-789012', $existingUser->google_id);
        $this->assertEquals('google', $existingUser->oauth_provider);
    }

    public function test_google_callback_handles_user_cancellation(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
        ]);

        $response = $this->get(route('social.callback', [
            'provider' => 'google',
            'error' => 'access_denied',
            'error_description' => 'The user denied request.',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_google_credentials_can_be_resolved_from_system_settings(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        SystemSetting::create([
            'key' => 'google_client_id',
            'value' => 'db-google-client-id.apps.googleusercontent.com',
            'group' => 'security',
            'type' => 'string',
            'is_public' => false,
        ]);

        SystemSetting::create([
            'key' => 'google_client_secret',
            'value' => 'db-google-secret',
            'group' => 'security',
            'type' => 'string',
            'is_public' => false,
        ]);

        $response = $this->get(route('social.redirect', 'google'));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }
}
