<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Security\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function __construct(
        protected TwoFactorAuthService $twoFactorService
    ) {}

    /**
     * Display the security and password settings view.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // If user is setting up 2FA, retrieve or generate pending secret
        $pendingSecret = $request->session()->get('pending_2fa_secret');
        $otpAuthUrl = null;

        if ($pendingSecret && !$user->two_factor_enabled) {
            $otpAuthUrl = $this->twoFactorService->getOtpAuthUrl(
                config('app.name', 'LocalDateHub'),
                $user->email,
                $pendingSecret
            );
        }

        return view('account.security', [
            'user' => $user,
            'pendingSecret' => $pendingSecret,
            'otpAuthUrl' => $otpAuthUrl,
        ]);
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'Your password has been changed successfully.');
    }

    /**
     * Start 2FA setup by generating a temporary Base32 secret.
     */
    public function generate2fa(Request $request): RedirectResponse
    {
        $secret = $this->twoFactorService->generateSecretKey();
        $request->session()->put('pending_2fa_secret', $secret);

        return back()->with('status', 'Two-Factor key generated. Enter the 6-digit code from your authenticator app to confirm.');
    }

    /**
     * Confirm and activate 2FA with a valid TOTP code.
     */
    public function confirm2fa(Request $request): RedirectResponse
    {
        $secret = $request->session()->get('pending_2fa_secret');
        if (!$secret) {
            return back()->withErrors(['two_factor_code' => 'Two-factor setup has expired. Please try again.']);
        }

        $request->validate([
            'two_factor_code' => ['required', 'string', 'size:6'],
        ]);

        if (!$this->twoFactorService->verifyKey($secret, $request->input('two_factor_code'))) {
            return back()->withErrors(['two_factor_code' => 'The verification code was incorrect. Please check your authenticator app.']);
        }

        $user = $request->user();
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ]);

        $request->session()->forget('pending_2fa_secret');

        return back()->with('status', 'Two-Factor Authentication is now active on your account!');
    }

    /**
     * Disable 2FA with current password confirmation.
     */
    public function disable2fa(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);

        return back()->with('status', 'Two-Factor Authentication has been disabled.');
    }

    /**
     * Delete the user's account (Self-service deletion complying with GDPR/CCPA).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user) {
            // Unpublish and soft-delete user's listings
            $user->listings()->update(['status' => 'archived']);
            $user->listings()->delete();

            // Soft-delete user account
            $user->delete();
        });

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Your account has been deleted.');
    }
}

