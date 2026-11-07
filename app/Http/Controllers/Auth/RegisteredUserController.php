<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Accounts\Models\UserConsent;
use App\Modules\Accounts\Models\UserPreference;
use App\Modules\Accounts\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $states = \App\Modules\Locations\Models\State::where('is_active', true)->orderBy('name')->get();

        return view('auth.register', [
            'states' => $states,
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Simple honeypot anti-bot check
        if (!empty($request->input('website_hp'))) {
            return redirect()->route('home');
        }

        $minAge = config('localdatehub.safety.min_age', 18);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'birthdate' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($minAge) {
                    $age = Carbon::parse($value)->age;
                    if ($age < $minAge) {
                        $fail("You must be at least {$minAge} years old to join LocalDateHub.");
                    }
                },
            ],
            'gender' => ['nullable', 'string', 'max:50'],
            'seeking' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'state_code' => ['required', 'string', 'size:2'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms_accepted' => ['required', 'accepted'],
            'anti_trafficking_confirmed' => ['required', 'accepted'],
            'age_confirmed_18' => ['required', 'accepted'],
        ], [
            'terms_accepted.accepted' => 'You must accept the Terms of Service and Privacy Policy to create an account.',
            'anti_trafficking_confirmed.accepted' => 'You must acknowledge the platform anti-trafficking and anti-solicitation policy.',
            'age_confirmed_18.accepted' => 'You must confirm that you are at least 18 years of age.',
        ]);

        $user = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => 'user',
                'account_status' => 'active',
                'is_age_verified' => true,
                'is_identity_verified' => false,
                'last_active_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            // Create initial user profile
            UserProfile::create([
                'user_id' => $user->id,
                'display_name' => $validated['name'],
                'bio' => null,
                'gender' => $validated['gender'] ?? null,
                'seeking' => $validated['seeking'] ?? null,
                'birthdate' => $validated['birthdate'],
                'city' => $validated['city'],
                'state_code' => strtoupper($validated['state_code']),
            ]);

            // Create initial user preferences
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

            // Audit record for age assurance
            AgeAssurance::create([
                'user_id' => $user->id,
                'method' => 'self_declaration',
                'status' => 'approved',
                'declared_birthdate' => $validated['birthdate'],
                'verification_metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                'verified_at' => now(),
            ]);

            // Audit records for mandatory legal consents
            UserConsent::create([
                'user_id' => $user->id,
                'consent_type' => 'adult_terms_18_plus',
                'version' => '1.0',
                'is_accepted' => true,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'consented_at' => now(),
            ]);

            UserConsent::create([
                'user_id' => $user->id,
                'consent_type' => 'anti_trafficking_acknowledgement',
                'version' => '1.0',
                'is_accepted' => true,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'consented_at' => now(),
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('age_confirmed_18', true);

        return redirect()->route('verification.notice');
    }
}
