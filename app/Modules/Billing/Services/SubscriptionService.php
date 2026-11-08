<?php

namespace App\Modules\Billing\Services;

use App\Models\User;
use App\Modules\Billing\Gateways\PaymentGatewayManager;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Billing\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SubscriptionService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected InvoiceService $invoiceService,
        protected FraudMonitoringService $fraudService
    ) {}

    /**
     * Create or upgrade a user's subscription.
     */
    public function createSubscription(User $user, PricingPlan $plan, array $paymentData = []): Subscription
    {
        $amountCents = $plan->price_cents;
        $ipAddress = $paymentData['ip_address'] ?? '127.0.0.1';

        // Fraud screening if paid
        if ($amountCents > 0) {
            $risk = $this->fraudService->assessRisk(
                user: $user,
                amountCents: $amountCents,
                ipAddress: $ipAddress,
                paymentToken: $paymentData['payment_token'] ?? null
            );

            if (! $risk['allowed']) {
                throw new RuntimeException($risk['message'] ?: 'Subscription purchase blocked by fraud controls.');
            }
        }

        $gateway = $this->gatewayManager->driver($paymentData['gateway'] ?? null);

        // Cancel any existing active subscriptions
        Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->update([
                'status' => 'canceled',
                'canceled_at' => now(),
                'auto_renew' => false,
            ]);

        $subResult = $gateway->createSubscription([
            'plan_id' => $plan->slug,
            'amount_cents' => $amountCents,
            'currency' => $plan->currency ?: 'USD',
            'user_email' => $user->email,
        ]);

        if (! $subResult->success) {
            throw new RuntimeException($subResult->errorMessage ?: 'Gateway subscription creation failed.');
        }

        $durationDays = $plan->duration_days ?: 30;
        $startsAt = now();
        $endsAt = now()->addDays($durationDays);
        $nextRenewalAt = $endsAt;

        return DB::transaction(function () use (
            $user,
            $plan,
            $gateway,
            $subResult,
            $startsAt,
            $endsAt,
            $nextRenewalAt,
            $amountCents,
            $paymentData,
            $ipAddress
        ) {
            $sub = Subscription::create([
                'user_id' => $user->id,
                'pricing_plan_id' => $plan->id,
                'status' => 'active',
                'gateway' => $gateway->getIdentifier(),
                'gateway_subscription_id' => $subResult->subscriptionId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'next_renewal_at' => $nextRenewalAt,
                'auto_renew' => true,
            ]);

            // Record initial transaction
            $referenceId = 'SUB-' . strtoupper(Str::random(10));
            $txn = PaymentTransaction::create([
                'user_id' => $user->id,
                'pricing_plan_id' => $plan->id,
                'reference_id' => $referenceId,
                'amount_cents' => $amountCents,
                'currency' => $plan->currency ?: 'USD',
                'payment_method' => 'card',
                'card_last_four' => $paymentData['card_last_four'] ?? '4242',
                'card_brand' => $paymentData['card_brand'] ?? 'Visa',
                'gateway' => $gateway->getIdentifier(),
                'gateway_transaction_id' => 'txn_' . Str::random(16),
                'status' => 'completed',
                'is_reconciled' => true,
                'reconciled_at' => now(),
                'gateway_payload' => [
                    'ip_address' => $ipAddress,
                    'subscription_id' => $sub->id,
                ],
            ]);

            // Generate Invoice
            $invoice = $this->invoiceService->createFromTransaction($txn);

            if ($user && $amountCents > 0) {
                app(\App\Modules\Notifications\Services\NotificationService::class)->send(
                    $user,
                    new \App\Modules\Notifications\Notifications\PaymentReceiptNotification($txn, $invoice),
                    'payment_receipt'
                );
            }

            return $sub;
        });
    }

    /**
     * Cancel an active subscription.
     */
    public function cancelSubscription(Subscription $subscription, ?string $reason = null): Subscription
    {
        $gateway = $this->gatewayManager->driver($subscription->gateway);
        if ($subscription->gateway_subscription_id) {
            $gateway->cancelSubscription($subscription->gateway_subscription_id);
        }

        $subscription->status = 'canceled';
        $subscription->canceled_at = now();
        $subscription->auto_renew = false;
        $subscription->save();

        return $subscription;
    }

    /**
     * Process pending renewals.
     *
     * @return array{processed: int, failed: int}
     */
    public function processRenewals(): array
    {
        $processed = 0;
        $failed = 0;

        $due = Subscription::where('status', 'active')
            ->where('auto_renew', true)
            ->where('next_renewal_at', '<=', now())
            ->with(['user', 'plan'])
            ->get();

        foreach ($due as $sub) {
            $plan = $sub->plan;
            $user = $sub->user;
            if (! $plan || ! $user) {
                continue;
            }

            try {
                $gateway = $this->gatewayManager->driver($sub->gateway);
                $referenceId = 'RNW-' . strtoupper(Str::random(10));

                $charge = $gateway->createCharge([
                    'amount_cents' => $plan->price_cents,
                    'currency' => $plan->currency ?: 'USD',
                    'reference_id' => $referenceId,
                ]);

                if ($charge->success) {
                    DB::transaction(function () use ($sub, $plan, $user, $charge, $referenceId, $gateway) {
                        $durationDays = $plan->duration_days ?: 30;
                        $sub->ends_at = ($sub->ends_at ?: now())->addDays($durationDays);
                        $sub->next_renewal_at = $sub->ends_at;
                        $sub->grace_period_ends_at = null;
                        $sub->save();

                        $txn = PaymentTransaction::create([
                            'user_id' => $user->id,
                            'pricing_plan_id' => $plan->id,
                            'reference_id' => $referenceId,
                            'amount_cents' => $plan->price_cents,
                            'currency' => $plan->currency ?: 'USD',
                            'payment_method' => 'card',
                            'gateway' => $gateway->getIdentifier(),
                            'gateway_transaction_id' => $charge->transactionId,
                            'status' => 'completed',
                            'is_reconciled' => true,
                            'reconciled_at' => now(),
                        ]);

                        $invoice = $this->invoiceService->createFromTransaction($txn);

                        app(\App\Modules\Notifications\Services\NotificationService::class)->send(
                            $user,
                            new \App\Modules\Notifications\Notifications\PaymentReceiptNotification($txn, $invoice),
                            'payment_receipt'
                        );
                    });
                    $processed++;
                } else {
                    $this->handleFailedRenewal($sub);
                    $failed++;
                }
            } catch (\Throwable) {
                $this->handleFailedRenewal($sub);
                $failed++;
            }
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    /**
     * Handle failed renewal attempt: enter grace period or expire.
     */
    protected function handleFailedRenewal(Subscription $sub): void
    {
        if (! $sub->grace_period_ends_at) {
            // Give 3 days grace period
            $sub->grace_period_ends_at = now()->addDays(3);
            $sub->status = 'past_due';
            $sub->save();
        } elseif (now()->isAfter($sub->grace_period_ends_at)) {
            $sub->status = 'expired';
            $sub->auto_renew = false;
            $sub->save();
        }
    }
}
