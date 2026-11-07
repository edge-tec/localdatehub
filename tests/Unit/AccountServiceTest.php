<?php

namespace Tests\Unit;

use App\Modules\Accounts\DTOs\RegisterAccountData;
use App\Modules\Accounts\Services\AccountService;
use App\Support\Exceptions\AgeVerificationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_registration_rejects_underage(): void
    {
        $service = new AccountService();

        $this->expectException(AgeVerificationException::class);

        // 16 years old
        $service->register(new RegisterAccountData(
            name: 'Young User',
            email: 'young@example.com',
            password: 'SecretPassword123!',
            birthdate: now()->subYears(16),
            termsAccepted: true,
            ageConfirmed: true,
        ));
    }

    public function test_account_registration_succeeds_for_adult(): void
    {
        $service = new AccountService();

        // 24 years old
        $user = $service->register(new RegisterAccountData(
            name: 'Adult User',
            email: 'adult@example.com',
            password: 'SecretPassword123!',
            birthdate: now()->subYears(24),
            termsAccepted: true,
            ageConfirmed: true,
        ));

        $this->assertNotNull($user->id);
        $this->assertEquals('adult@example.com', $user->email);
        $this->assertDatabaseHas('users', [
            'email' => 'adult@example.com',
        ]);
    }
}
