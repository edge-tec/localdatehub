<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\DTOs\SubscriptionPlanDTO;
use Illuminate\Support\Collection;

class BillingService
{
    /**
     * Get available membership / feature tiers.
     *
     * @return Collection<int, SubscriptionPlanDTO>
     */
    public function getPlans(): Collection
    {
        return collect([
            new SubscriptionPlanDTO(
                id: 'plan-basic',
                name: 'Standard Adult Member',
                description: 'Full access to verified local dating discovery and messaging.',
                priceCents: 0,
                currency: 'USD',
                interval: 'lifetime',
                features: [
                    'Verified 18+ profile badge',
                    'Privacy-safe proximity discovery',
                    'Direct encrypted messaging',
                    'Curated local date spot proposals',
                ],
                isFeatured: false,
            ),
            new SubscriptionPlanDTO(
                id: 'plan-premium',
                name: 'LocalDateHub Plus',
                description: 'Enhanced privacy controls and priority local discovery.',
                priceCents: 999,
                currency: 'USD',
                interval: 'month',
                features: [
                    'All Standard Member features',
                    'Stealth mode (Hide distance & online status)',
                    'Unlimited daily date proposals',
                    'Priority verified safety queue',
                    'Featured profile highlight in city searches',
                ],
                isFeatured: true,
            ),
        ]);
    }
}
