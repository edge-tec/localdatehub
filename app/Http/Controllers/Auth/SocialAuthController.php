<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Accounts\Models\UserConsent;
use App\Modules\Accounts\Models\UserPreference;
use App\Modules\Accounts\Models\UserProfile;
use App\Modules\Admin\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Supported OAuth providers.
     */
    protected array $allowedProviders = ['google', 'facebook'];

    /**
     * Redirect the user to the OAuth provider.
     */
    public function redirect(string $provider): RedirectResponse
    {
        if (!in_array($provider, $this->allowedProviders, true)) {
            return redirect()->route('login')->with('error', 'Unsupported authentication provider.');
        }

        if (!$this->configureProvider($provider)) {
            $providerName = ucfirst($provider);
            $msg = Auth::check() && Auth::user()->isStaff()
                ? "{$providerName} OAuth credentials are not configured. Please set {$providerName}_CLIENT_ID and {$providerName}_CLIENT_SECRET in your .env or Admin Settings Suite."
                : "{$providerName} Sign-In is currently being configured. Please sign in using your email and password or check back shortly.";

            return redirect()->route('login')->with('error', $msg);
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Throwable $e) {
            Log::error("{$provider} OAuth redirect error: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('login')->with('error', "Unable to connect to {$provider}: " . $e->getMessage());
        }
    }

    /**
     * Handle the OAuth provider callback.
     */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        if (!in_array($provider, $this->allowedProviders, true)) {
            return redirect()->route('login')->with('error', 'Unsupported authentication provider.');
        }

        // Check if user cancelled authorization or provider returned an error
        if ($request->has('error') || $request->has('error_reason')) {
            $err = $request->get('error_description') ?: $request->get('error_reason') ?: $request->get('error');
            return redirect()->route('login')->with('error', ucfirst($provider) . ' sign-in was cancelled: ' . $err);
        }

        if (!$this->configureProvider($provider)) {
            return redirect()->route('login')->with('error', ucfirst($provider) . ' OAuth credentials are not configured.');
        }

        try {
            // Attempt standard retrieval; fall back to stateless if state mismatch occurs
            try {
                $socialUser = Socialite::driver($provider)->user();
            } catch (\Throwable $stateException) {
                $socialUser = Socialite::driver($provider)->stateless()->user();
            }
        } catch (\Throwable $e) {
            Log::error("{$provider} OAuth callback retrieval error: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('login')->with('error', 'Failed to authenticate with ' . ucfirst($provider) . ': ' . $e->getMessage());
        }

        $oauthId = (string) $socialUser->getId();
        $email = $socialUser->getEmail();
        $name = $socialUser->getName() ?: ($socialUser->getNickname() ?: explode('@', (string) $email)[0]);
        $avatar = $socialUser->getAvatar();

        if (empty($email)) {
            return redirect()->route('login')->with('error', 'Unable to retrieve your verified email address from ' . ucfirst($provider) . '. Please register manually.');
        }

        // Find existing user by OAuth ID or by Email
        $user = User::where(function ($query) use ($provider, $oauthId) {
            $query->where('oauth_provider', $provider)->where('oauth_id', $oauthId);
            if ($provider === 'google') {
                $query->orWhere('google_id', $oauthId);
            }
        })->first();

        if (!$user) {
            // Find by matching email
            $user = User::where('email', strtolower($email))->first();
        }

        if ($user) {
            // Link OAuth credentials to existing user
            if ($provider === 'google') {
                $user->google_id = $oauthId;
            }
            $user->oauth_provider = $provider;
            $user->oauth_id = $oauthId;

            if (empty($user->email_verified_at)) {
                $user->email_verified_at = now();
            }

            if (empty($user->avatar) && !empty($avatar)) {
                $user->avatar = $avatar;
            }

            $user->last_active_at = now();
            $user->last_login_ip = $request->ip();
            $user->save();
        } else {
            // Create a brand new user
            $baseUsername = Str::slug(explode('@', $email)[0], '_');
            if (empty($baseUsername)) {
                $baseUsername = 'user';
            }

            $username = $baseUsername;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . '_' . rand(100, 9999);
            }

            $user = DB::transaction(function () use ($name, $username, $email, $provider, $oauthId, $avatar, $request) {
                $user = User::create([
                    'name' => $name,
                    'username' => $username,
                    'email' => strtolower($email),
                    'google_id' => $provider === 'google' ? $oauthId : null,
                    'oauth_provider' => $provider,
                    'oauth_id' => $oauthId,
                    'avatar' => $avatar,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'user',
                    'account_status' => 'active',
                    'is_age_verified' => true,
                    'is_identity_verified' => false,
                    'email_verified_at' => now(),
                    'last_active_at' => now(),
                    'last_login_ip' => $request->ip(),
                ]);

                UserProfile::create([
                    'user_id' => $user->id,
                    'display_name' => $name,
                    'bio' => null,
                    'gender' => null,
                    'seeking' => null,
                    'birthdate' => now()->subYears(21)->format('Y-m-d'),
                    'city' => 'Austin',
                    'state_code' => 'TX',
                    'avatar_path' => $avatar,
                ]);

                UserPreference::create([
                    'user_id' => $user->id,
                    'notification_settings' => [
                        'email_messages' => true,
                        'email_promotions' => false,
                        'security_alerts' => true,
                    ],
                    'privacy_settings' => [
                        'hide_location_details' => false,
                        'hide_age' => false,
                    ],
                    'allow_contact_messages' => true,
                    'require_verified_senders' => false,
                ]);

                AgeAssurance::create([
                    'user_id' => $user->id,
                    'method' => 'oauth_' . $provider,
                    'status' => 'approved',
                    'declared_birthdate' => now()->subYears(21)->format('Y-m-d'),
                    'verification_metadata' => [
                        'provider' => $provider,
                        'oauth_id' => $oauthId,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                ]);

                UserConsent::create([
                    'user_id' => $user->id,
                    'consent_type' => 'terms_of_service',
                    'version' => '1.0',
                    'is_accepted' => true,
                    'consented_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return $user;
            });
        }

        // Account status verification
        if (in_array($user->account_status, ['suspended', 'banned', 'deactivated'], true)) {
            return redirect()->route('login')->with('error', 'Your account has been ' . $user->account_status . '. Please contact support.');
        }

        // Authenticate user
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'))->with('success', "Welcome, {$user->name}! You have signed in with " . ucfirst($provider) . ".");
    }

    /**
     * Resolve and apply OAuth credentials dynamically from .env, config or SystemSetting.
     */
    protected function configureProvider(string $provider): bool
    {
        $clientId = config("services.{$provider}.client_id")
            ?: SystemSetting::get("{$provider}_client_id");

        $clientSecret = config("services.{$provider}.client_secret")
            ?: SystemSetting::get("{$provider}_client_secret");

        if (empty($clientId) || empty($clientSecret)) {
            return false;
        }

        $redirectUri = config("services.{$provider}.redirect")
            ?: (env('APP_URL') ? rtrim(env('APP_URL'), '/') . "/auth/{$provider}/callback" : url("/auth/{$provider}/callback"));

        config([
            "services.{$provider}.client_id" => $clientId,
            "services.{$provider}.client_secret" => $clientSecret,
            "services.{$provider}.redirect" => $redirectUri,
        ]);

        return true;
    }
}
