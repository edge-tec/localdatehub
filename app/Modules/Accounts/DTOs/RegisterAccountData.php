<?php

namespace App\Modules\Accounts\DTOs;

use DateTimeInterface;

readonly class RegisterAccountData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public DateTimeInterface $birthdate,
        public ?string $phone = null,
        public bool $termsAccepted = true,
        public bool $ageConfirmed = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: strtolower(trim($data['email'])),
            password: $data['password'],
            birthdate: $data['birthdate'] instanceof DateTimeInterface 
                ? $data['birthdate'] 
                : new \DateTimeImmutable($data['birthdate']),
            phone: $data['phone'] ?? null,
            termsAccepted: (bool) ($data['terms_accepted'] ?? true),
            ageConfirmed: (bool) ($data['age_confirmed'] ?? true),
        );
    }
}
