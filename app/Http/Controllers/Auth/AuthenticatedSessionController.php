<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Security\TwoFactorAuthService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        protected TwoFactorAuthService $twoFactorService
    ) {}

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$request->filled('login') && $request->filled('email')) {
            $request->merge(['login' => $request->input('email')]);
        }

        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $loginField = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $loginField => $request->input('login'),
            'password' => $request->input('password'),
        ];

        // Check if user exists and is suspended before checking credentials
        $user = User::where($loginField, $request->input('login'))->first();
        if ($user && $user->isSuspended()) {
            RateLimiter::clear($this->throttleKey($request));
            return redirect()->route('account.suspended');
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        /** @var User $authenticatedUser */
        $authenticatedUser = Auth::user();

        // Check 2FA requirement
        if ($authenticatedUser->two_factor_enabled && !empty($authenticatedUser->two_factor_secret)) {
            // Check if 2FA code is provided
            $twoFactorCode = $request->input('two_factor_code');
            if (empty($twoFactorCode)) {
                // Keep user ID in session for challenge
                $request->session()->put('2fa_user_id', $authenticatedUser->id);
                $request->session()->put('2fa_remember', $request->boolean('remember'));
                Auth::logout();
                return redirect()->route('login.2fa');
            }

            if (!$this->twoFactorService->verifyKey($authenticatedUser->two_factor_secret, $twoFactorCode)) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'two_factor_code' => 'The provided two-factor authentication code is invalid.',
                ]);
            }
        }

        $authenticatedUser->update([
            'last_active_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();
        $request->session()->put('age_confirmed_18', true);

        return redirect()->intended(route('home'));
    }

    /**
     * Display 2FA challenge screen.
     */
    public function show2fa(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify 2FA challenge code.
     */
    public function verify2fa(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'two_factor_code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::findOrFail($userId);

        if (!$this->twoFactorService->verifyKey($user->two_factor_secret, $request->input('two_factor_code'))) {
            throw ValidationException::withMessages([
                'two_factor_code' => 'The two-factor authentication code is invalid or has expired.',
            ]);
        }

        $remember = $request->session()->get('2fa_remember', false);
        $request->session()->forget(['2fa_user_id', '2fa_remember']);

        Auth::login($user, $remember);
        $user->update([
            'last_active_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();
        $request->session()->put('age_confirmed_18', true);

        return redirect()->intended(route('home'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('login')) . '|' . $request->ip());
    }
}

