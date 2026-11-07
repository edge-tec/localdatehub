<?php

namespace App\Modules\Accounts\Services;

use App\Models\User;
use App\Modules\Accounts\DTOs\RegisterAccountData;
use App\Support\Exceptions\AgeVerificationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AccountService
{
    /**
     * Verify age requirement and register a new user.
     *
     * @param  RegisterAccountData  $data
     * @return User
     * @throws AgeVerificationException
     */
    public function register(RegisterAccountData $data): User
    {
        $minAge = config('localdatehub.safety.min_age', 18);
        $age = Carbon::parse($data->birthdate)->age;

        if ($age < $minAge) {
            throw new AgeVerificationException("You must be at least {$minAge} years old to register on LocalDateHub.");
        }

        if (!$data->ageConfirmed || !$data->termsAccepted) {
            throw new AgeVerificationException("You must explicitly confirm your age and accept terms to proceed.");
        }

        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        return $user;
    }

    /**
     * Check if a given birthdate meets 18+ requirement.
     */
    public function isAgeEligible(\DateTimeInterface|string $birthdate): bool
    {
        $minAge = config('localdatehub.safety.min_age', 18);
        return Carbon::parse($birthdate)->age >= $minAge;
    }
}
