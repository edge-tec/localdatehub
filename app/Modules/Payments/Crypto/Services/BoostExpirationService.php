<?php

namespace App\Modules\Payments\Crypto\Services;

use App\Modules\Admin\Services\AdminAuditService;
use App\Modules\Listings\Models\Listing;
use App\Modules\Payments\Crypto\Models\ListingBoost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BoostExpirationService
{
    /**
     * Expire active boosts whose expiration date has passed and restore listing status.
     *
     * @return int Number of boosts expired
     */
    public function expireOutdatedBoosts(): int
    {
        $now = now();
        $expiredBoosts = ListingBoost::where('status', ListingBoost::STATUS_ACTIVE)
            ->where('expires_at', '<=', $now)
            ->with(['listing', 'user'])
            ->get();

        $count = 0;

        foreach ($expiredBoosts as $boost) {
            DB::transaction(function () use ($boost, &$count) {
                $boost->update(['status' => ListingBoost::STATUS_EXPIRED]);
                $count++;

                $listing = $boost->listing;
                if ($listing) {
                    // Check if listing has any other remaining active boosts
                    $hasOtherActiveBoost = ListingBoost::where('listing_id', $listing->id)
                        ->where('id', '!=', $boost->id)
                        ->where('status', ListingBoost::STATUS_ACTIVE)
                        ->where('expires_at', '>', now())
                        ->exists();

                    if (!$hasOtherActiveBoost) {
                        $listing->update([
                            'is_featured' => false,
                            'promoted_until' => null,
                        ]);

                        Log::info("Restored normal ranking for listing #{$listing->id} after boost #{$boost->id} expiration.");
                    }
                }

                AdminAuditService::log(
                    action: 'crypto_payment.boost_expired',
                    target: $boost,
                    before: ['status' => ListingBoost::STATUS_ACTIVE],
                    after: ['status' => ListingBoost::STATUS_EXPIRED]
                );
            });
        }

        return $count;
    }
}

