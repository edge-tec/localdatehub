<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\SystemSetting;
use App\Services\TurnstileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareTurnstileTest extends TestCase
{
    use RefreshDatabase;

    protected function enableTurnstile(string $siteKey = '0x4AAAAAAATestSiteKey', string $secretKey = '0x4AAAAAAATestSecretKey'): void
    {
        SystemSetting::updateOrCreate(['key' => 'turnstile_enabled'], ['value' => 'true', 'type' => 'boolean', 'group' => 'security']);
        SystemSetting::updateOrCreate(['key' => 'turnstile_site_key'], ['value' => $siteKey, 'type' => 'string', 'group' => 'security']);
        SystemSetting::updateOrCreate(['key' => 'turnstile_secret_key'], ['value' => $secretKey, 'type' => 'string', 'group' => 'security']);
    }

    public function test_turnstile_is_inactive_when_unconfigured_and_does_not_block_login(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => bcrypt('SecretPass123!'),
            'account_status' => 'active',
        ]);

        $this->assertFalse(app(TurnstileService::class)->isEnabled());

        $this->get(route('login'))
            ->assertStatus(200)
            ->assertDontSee('cf-turnstile');

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'SecretPass123!',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_enabled_turnstile_renders_widget_on_auth_and_contact_forms(): void
    {
        $this->enableTurnstile('0x4AAAAAAACustomSiteKey', '0x4AAAAAAACustomSecret');

        $this->assertTrue(app(TurnstileService::class)->isEnabled());

        foreach ([route('login'), route('register'), route('password.request'), route('contact')] as $url) {
            $this->get($url)
                ->assertStatus(200)
                ->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js', false)
                ->assertSee('class="cf-turnstile"', false)
                ->assertSee('data-sitekey="0x4AAAAAAACustomSiteKey"', false);
        }
    }

    public function test_login_fails_when_turnstile_is_enabled_and_token_is_missing_or_invalid(): void
    {
        $this->enableTurnstile();

        $user = User::factory()->create([
            'email' => 'protected@example.com',
            'password' => bcrypt('SecretPass123!'),
            'account_status' => 'active',
        ]);

        // Missing token
        $this->from(route('login'))
            ->post('/login', [
                'login' => $user->email,
                'password' => 'SecretPass123!',
            ])
            ->assertSessionHasErrors(['cf-turnstile-response']);

        $this->assertGuest();

        // Invalid token rejected by Cloudflare API
        Http::fake([
            TurnstileService::VERIFY_URL => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $this->from(route('login'))
            ->post('/login', [
                'login' => $user->email,
                'password' => 'SecretPass123!',
                'cf-turnstile-response' => 'bad-token',
            ])
            ->assertSessionHasErrors(['cf-turnstile-response']);

        $this->assertGuest();
    }

    public function test_login_and_contact_succeed_with_valid_turnstile_token(): void
    {
        $this->enableTurnstile('0x4AAAAAAASiteKey', '0x4AAAAAAASecretKey');

        Http::fake([
            TurnstileService::VERIFY_URL => Http::response([
                'success' => true,
            ], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'verified-human@example.com',
            'password' => bcrypt('SecretPass123!'),
            'account_status' => 'active',
        ]);

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'SecretPass123!',
            'cf-turnstile-response' => 'valid-cf-token',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);

        Http::assertSent(function ($request) {
            return $request->url() === TurnstileService::VERIFY_URL
                && $request['secret'] === '0x4AAAAAAASecretKey'
                && $request['response'] === 'valid-cf-token';
        });

        $this->post(route('pages.contact.submit'), [
            'name' => 'Alex Human',
            'email' => 'alex@example.com',
            'inquiry_type' => 'general_support',
            'subject' => 'Question about account',
            'message' => 'Hello, I have a question regarding my account settings.',
            'cf-turnstile-response' => 'valid-cf-token',
        ])->assertSessionHasNoErrors();
    }
}
