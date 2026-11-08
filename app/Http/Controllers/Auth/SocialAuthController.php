<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the OAuth provider.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $allowedProviders = ['google', 'facebook'];

        if (!in_array($provider, $allowedProviders, true)) {
            return redirect()->route('login')->with('error', 'Unsupported authentication provider.');
        }

        $clientId = config("services.{$provider}.client_id");
        $clientSecret = config("services.{$provider}.client_secret");

        if (empty($clientId) || empty($clientSecret)) {
            $providerName = ucfirst($provider);
            $msg = Auth::check() && Auth::user()->isStaff()
                ? "{$providerName} OAuth credentials are not configured in services.php / .env. Please set {$providerName}_CLIENT_ID and {$providerName}_CLIENT_SECRET."
                : "{$providerName} sign-in is currently unavailable. Please sign in using your email and password.";

            return redirect()->route('login')->with('error', $msg);
        }

        // If Socialite class exists in the environment
        if (class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
            /** @var \Laravel\Socialite\Contracts\Factory $socialite */
            $socialite = app(\Laravel\Socialite\Contracts\Factory::class);
            return $socialite->driver($provider)->redirect();
        }

        return redirect()->route('login')->with('error', ucfirst($provider) . ' sign-in driver is not installed. Please use your email and password.');
    }

    /**
     * Handle the OAuth provider callback.
     */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        return redirect()->route('login')->with('error', ucfirst($provider) . ' authentication completed callback.');
    }
}

