<?php

namespace App\Modules\Billing\DTOs;

readonly class SubscriptionPlanDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public int $priceCents,
        public string $currency,
        public string $interval,
        public array $features,
        public bool $isFeatured = false,
    ) {}
}
