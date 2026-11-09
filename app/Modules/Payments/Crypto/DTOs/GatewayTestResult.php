<?php

namespace App\Modules\Payments\Crypto\DTOs;

class GatewayTestResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public array $details = []
    ) {}

    public static function passed(string $message = 'API connection successful and credentials verified.', array $details = []): self
    {
        return new self(success: true, message: $message, details: $details);
    }

    public static function failed(string $message, array $details = []): self
    {
        return new self(success: false, message: $message, details: $details);
    }
}

