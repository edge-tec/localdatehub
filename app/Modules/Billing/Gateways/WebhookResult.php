<?php

namespace App\Modules\Billing\Gateways;

class WebhookResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $eventId = null,
        public readonly ?string $eventType = null,
        public readonly ?string $transactionReference = null,
        public readonly ?string $gatewayTransactionId = null,
        public readonly ?string $subscriptionId = null,
        public readonly int $amountCents = 0,
        public readonly array $data = [],
        public readonly ?string $errorMessage = null
    ) {}
}