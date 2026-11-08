<?php

namespace App\Modules\Billing\Gateways;

class RefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $refundId = null,
        public readonly int $amountCents = 0,
        public readonly ?string $errorMessage = null,
        public readonly array $rawPayload = []
    ) {}
}
