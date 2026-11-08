<?php

namespace App\Modules\Billing\Gateways;

class ChargeResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId = null,
        public readonly ?string $referenceId = null,
        public readonly int $amountCents = 0,
        public readonly string $currency = 'USD',
        public readonly ?string $cardLastFour = null,
        public readonly ?string $cardBrand = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawPayload = []
    ) {}
}