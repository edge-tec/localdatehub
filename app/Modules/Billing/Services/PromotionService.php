<?php

namespace App\Modules\Billing\Services;

use App\Models\User;
use App\Modules\Billing\Gateways\PaymentGatewayManager;
use App\Modules\Billing\Models\Coupon;
use App\Modules\Billing\Models\FeaturedListing;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Billing\Models\PricingRule;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\State;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PromotionService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected InvoiceService $invoiceService,
        protected FraudMonitoringService $fraudService
    ) {}

    /**
     * Calculate dynamic pricing for a plan given optional category, state, and coupon.
     */
    public function calculatePrice(
        PricingPlan $plan,
        ?Category $category = null,
        ?State $state = null,
        ?Coupon $coupon = null
    ): array {
        $baseCents = $plan->price_cents;
        $finalCents = $baseCents;
        $appliedRule = null;
        $discountAmount = 0;

        // 1. Check for Category / State / Plan Dynamic Pricing Rules
        $rule = PricingRule::active()
            ->where(function ($q) use ($plan, $category, $state) {
                $q->where('pricing_plan_id', $plan->id);
                if ($category) {
                    $q->orWhere('category_id', $category->id);
                }
                if ($state) {
                    $q->orWhere('state_id', $state->id);
                }
            })
            ->first();

        if ($rule) {
            $appliedRule = $rule->rule_name;
            if ($rule->is_free) {
                $finalCents = 0;
            } elseif ($rule->fixed_price_cents !== null) {
                $finalCents = $rule->fixed_price_cents;
            } elseif ($rule->discount_percentage > 0) {
                $discount = (int) round(($baseCents * $rule->discount_percentage) / 100);
                $finalCents = max(0, $baseCents - $discount);
                $discountAmount = $discount;
            }
        }

        // 2. Check for Promotional Coupon
        if ($coupon && $coupon->is_active && $finalCents > 0) {
            if ($coupon->discount_type === 'percentage') {
                $couponDiscount = (int) round(($finalCents * $coupon->discount_value) / 100);
                $finalCents = max(0, $finalCents - $couponDiscount);
                $discountAmount += $couponDiscount;
            } elseif ($coupon->discount_type === 'fixed') {
                $couponDiscount = min($finalCents, $coupon->discount_value);
                $finalCents = max(0, $finalCents - $couponDiscount);
                $discountAmount += $couponDiscount;
            }
        }

        return [
            'base_price_cents' => $baseCents,
            'final_price_cents' => $finalCents,
            'discount_cents' => $discountAmount,
            'applied_rule' => $appliedRule,
            'is_free' => $finalCents === 0,
        ];
    }

    /**
     * Promote a listing with a selected pricing plan.
     */
    public function promoteListing(
        Listing $listing,
        PricingPlan $plan,
        array $paymentData,
        User $user,
        ?string $placement = null,
        ?Coupon $coupon = null
    ): array {
        $placement = $placement ?: 'category_top';
        $category = $listing->category;
        $state = $listing->state;

        $pricing = $this->calculatePrice($plan, $category, $state, $coupon);
        $amountCents = $pricing['final_price_cents'];
        $ipAddress = $paymentData['ip_address'] ?? '127.0.0.1';

        // Perform fraud risk screening if amount > 0
        if ($amountCents > 0) {
            $risk = $this->fraudService->assessRisk(
                user: $user,
                amountCents: $amountCents,
                ipAddress: $ipAddress,
                paymentToken: $paymentData['payment_token'] ?? null
            );

            if (! $risk['allowed']) {
                throw new RuntimeException($risk['message'] ?: 'Transaction declined due to fraud risk.');
            }
        }

        $durationDays = $plan->duration_days ?: 7;
        $startsAt = now();
        $endsAt = now()->addDays($durationDays);
        $referenceId = 'TXN-' . strtoupper(Str::random(10));

        $gateway = $this->gatewayManager->driver($paymentData['gateway'] ?? null);

        // Execute Charge via Gateway
        $charge = null;
        if ($amountCents > 0) {
            $charge = $gateway->createCharge([
                'amount_cents' => $amountCents,
                'currency' => $plan->currency ?: 'USD',
                'reference_id' => $referenceId,
                'payment_token' => $paymentData['payment_token'] ?? 'tok_sandbox_default',
                'card_last_four' => $paymentData['card_last_four'] ?? '4242',
                'card_brand' => $paymentData['card_brand'] ?? 'Visa',
            ]);

            if (! $charge->success) {
                throw new RuntimeException($charge->errorMessage ?: 'Payment processing failed.');
            }
        }

        return DB::transaction(function () use (
            $listing,
            $plan,
            $user,
            $placement,
            $amountCents,
            $startsAt,
            $endsAt,
            $referenceId,
            $charge,
            $coupon,
            $gateway,
            $ipAddress
        ) {
            // Record Payment Transaction
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'pricing_plan_id' => $plan->id,
                'listing_id' => $listing->id,
                'reference_id' => $referenceId,
                'amount_cents' => $amountCents,
                'currency' => $plan->currency ?: 'USD',
                'payment_method' => 'card',
                'card_last_four' => $charge?->cardLastFour ?? '4242',
                'card_brand' => $charge?->cardBrand ?? 'Visa',
                'gateway' => $gateway->getIdentifier(),
                'gateway_transaction_id' => $charge?->transactionId ?? 'free_' . Str::random(12),
                'status' => 'completed',
                'is_reconciled' => true,
                'reconciled_at' => now(),
                'gateway_payload' => [
                    'ip_address' => $ipAddress,
                    'placement' => $placement,
                    'duration_days' => $plan->duration_days,
                ],
            ]);

            // Create Featured Listing Record
            $featured = FeaturedListing::create([
                'listing_id' => $listing->id,
                'user_id' => $user->id,
                'pricing_plan_id' => $plan->id,
                'placement' => $placement,
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'is_active' => true,
            ]);

            // Update Listing attributes
            $listing->is_featured = true;
            $listing->promoted_until = $endsAt;
            if ($plan->plan_type === 'bump') {
                $listing->bumped_at = now();
            }
            $listing->save();

            // Track coupon use if applied
            if ($coupon) {
                $coupon->increment('uses_count');
            }

            // Generate Invoice
            $invoice = $this->invoiceService->createFromTransaction($transaction);

            return [
                'transaction' => $transaction,
                'featured_listing' => $featured,
                'invoice' => $invoice,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ];
        });
    }

    /**
     * Bump a listing immediately to the top of category & city sorting.
     */
    public function bumpListing(Listing $listing, User $user): Listing
    {
        $listing->bumped_at = now();
        $listing->save();

        return $listing;
    }

    /**
     * Batch process expired promotions.
     */
    public function expireOutdatedPromotions(): int
    {
        $now = now();
        $expiredCount = 0;

        $expiredFeatured = FeaturedListing::where('is_active', true)
            ->where('ends_at', '<', $now)
            ->get();

        foreach ($expiredFeatured as $feat) {
            $feat->is_active = false;
            $feat->status = 'expired';
            $feat->save();

            // Check if listing has any other active promotions
            $hasOtherActive = FeaturedListing::where('listing_id', $feat->listing_id)
                ->where('is_active', true)
                ->where('ends_at', '>=', $now)
                ->exists();

            if (! $hasOtherActive) {
                $feat->listing->is_featured = false;
                $feat->listing->promoted_until = null;
                $feat->listing->save();
            }

            $expiredCount++;
        }

        return $expiredCount;
    }
}