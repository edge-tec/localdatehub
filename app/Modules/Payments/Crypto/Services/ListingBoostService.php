<?php

namespace App\Modules\Payments\Crypto\Services;

use App\Modules\Admin\Services\AdminAuditService;
use App\Modules\Listings\Models\Listing;
use App\Modules\Notifications\Notifications\PaymentReceiptNotification;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Models\ListingBoost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListingBoostService
{
    /**
     * Atomically activate post boost from a confirmed cryptocurrency payment.
     */
    public function activateBoost(CryptoPayment $payment): ?ListingBoost
    {
        return DB::transaction(function () use ($payment) {
            // Re-fetch payment with pessimistic lock to prevent race conditions & duplicate webhooks
            $lockedPayment = CryptoPayment::where('id', $payment->id)->lockForUpdate()->first();

            if (!$lockedPayment) {
                return null;
            }

            // Check if boost has already been created (Idempotency protection)
            $existingBoost = ListingBoost::where('payment_id', $lockedPayment->id)->first();
            if ($existingBoost) {
                Log::info("ListingBoost already exists for payment #{$lockedPayment->id}, returning existing boost.");
                return $existingBoost;
            }

            // Mark payment as completed if not already marked
            if ($lockedPayment->status !== CryptoPayment::STATUS_COMPLETED) {
                $lockedPayment->status = CryptoPayment::STATUS_COMPLETED;
            }
            if (!$lockedPayment->confirmed_at) {
                $lockedPayment->confirmed_at = now();
            }
            $lockedPayment->save();

            $plan = $lockedPayment->boostPlan;
            $listing = Listing::where('id', $lockedPayment->listing_id)->lockForUpdate()->first();

            if (!$listing) {
                Log::error("Listing #{$lockedPayment->listing_id} not found during boost activation for payment #{$lockedPayment->id}");
                return null;
            }

            $durationDays = $plan?->duration_days ?? 7;
            $startsAt = now();
            
            // If listing already has an active boost, extend from current expiry
            $currentActiveBoost = ListingBoost::where('listing_id', $listing->id)
                ->where('status', ListingBoost::STATUS_ACTIVE)
                ->where('expires_at', '>', now())
                ->orderByDesc('expires_at')
                ->first();

            $expiresAt = $currentActiveBoost 
                ? $currentActiveBoost->expires_at->addDays($durationDays)
                : now()->addDays($durationDays);

            // 1. Create the listing boost record
            $boost = ListingBoost::create([
                'listing_id' => $listing->id,
                'user_id' => $lockedPayment->user_id,
                'payment_id' => $lockedPayment->id,
                'boost_plan_id' => $lockedPayment->boost_plan_id,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'status' => ListingBoost::STATUS_ACTIVE,
            ]);

            // 2. Elevate listing ranking, feature status, and bump timestamp
            $listing->update([
                'is_featured' => true,
                'promoted_until' => $expiresAt,
                'bumped_at' => now(),
            ]);

            // 3. Dispatch user notification if user exists
            if ($lockedPayment->user) {
                try {
                    $lockedPayment->user->notify(new PaymentReceiptNotification(
                        transactionId: $lockedPayment->internal_order_id,
                        amount: $lockedPayment->fiat_amount,
                        currency: $lockedPayment->fiat_currency,
                        description: "Post Boost Activated: {$plan?->name} for listing '{$listing->title}'"
                    ));
                } catch (\Throwable $e) {
                    Log::warning("Failed to deliver boost notification: {$e->getMessage()}");
                }
            }

            // 4. Record audit log
            AdminAuditService::log(
                action: 'crypto_payment.boost_activated',
                target: $boost,
                before: ['status' => $lockedPayment->status],
                after: [
                    'boost_id' => $boost->id,
                    'listing_id' => $listing->id,
                    'plan' => $plan?->name,
                    'starts_at' => $startsAt->toIso8601String(),
                    'expires_at' => $expiresAt->toIso8601String(),
                ]
            );

            Log::info("Post Boost #{$boost->id} successfully activated for listing #{$listing->id} until {$expiresAt->toIso8601String()}");

            return $boost;
        });
    }
}

