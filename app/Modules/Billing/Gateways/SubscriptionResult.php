<?php

namespace App\Modules\Billing\Gateways;

class SubscriptionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $subscriptionId = null,
        public readonly ?string $status = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawPayload = []
    ) {}
}