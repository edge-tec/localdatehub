<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Gateways\PaymentGatewayManager;
use App\Modules\Billing\Models\FeaturedListing;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\PaymentWebhook;
use App\Modules\Billing\Models\Refund;
use App\Modules\Billing\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WebhookHandlingService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Handle incoming gateway webhook with signature verification & idempotency.
     */
    public function handle(Request $request, string $gatewayName): array
    {
        $gateway = $this->gatewayManager->driver($gatewayName);

        // 1. Signature Verification
        if (! $gateway->verifyWebhookSignature($request)) {
            Log::warning("Invalid webhook signature received for gateway [{$gatewayName}].");
            throw new HttpException(400, 'Invalid webhook signature.');
        }

        // 2. Parse Payload
        $result = $gateway->parseWebhook($request);
        if (! $result->valid || ! $result->eventId) {
            throw new HttpException(422, 'Malformed webhook event.');
        }

        // 3. Idempotency Check
        $existingWebhook = PaymentWebhook::where('gateway', $gatewayName)
            ->where('event_id', $result->eventId)
            ->first();

        if ($existingWebhook && $existingWebhook->is_processed) {
            return [
                'status' => 'already_processed',
                'event_id' => $result->eventId,
                'gateway' => $gatewayName,
            ];
        }

        $webhookLog = $existingWebhook ?: PaymentWebhook::create([
            'gateway' => $gatewayName,
            'event_id' => $result->eventId,
            'event_type' => $result->eventType ?: 'unknown',
            'payload' => json_encode($result->data),
            'is_processed' => false,
        ]);

        // 4. Process Event Action
        try {
            DB::transaction(function () use ($result, $gatewayName) {
                switch ($result->eventType) {
                    case 'charge.succeeded':
                        $this->handleChargeSucceeded($result, $gatewayName);
                        break;

                    case 'charge.failed':
                        $this->handleChargeFailed($result);
                        break;

                    case 'charge.refunded':
                        $this->handleChargeRefunded($result);
                        break;

                    case 'subscription.renewed':
                        $this->handleSubscriptionRenewed($result);
                        break;

                    case 'subscription.canceled':
                        $this->handleSubscriptionCanceled($result);
                        break;

                    default:
                        Log::info("Unhandled webhook event [{$result->eventType}] for [{$gatewayName}].");
                }
            });

            $webhookLog->is_processed = true;
            $webhookLog->processed_at = now();
            $webhookLog->save();

            return [
                'status' => 'success',
                'event_id' => $result->eventId,
                'event_type' => $result->eventType,
            ];
        } catch (\Throwable $e) {
            $webhookLog->error_message = $e->getMessage();
            $webhookLog->save();

            Log::error("Failed to process webhook [{$result->eventId}]: " . $e->getMessage());
            throw $e;
        }
    }

    protected function handleChargeSucceeded($result, string $gatewayName): void
    {
        $txn = null;
        if ($result->transactionReference) {
            $txn = PaymentTransaction::where('reference_id', $result->transactionReference)->first();
        }
        if (! $txn && $result->gatewayTransactionId) {
            $txn = PaymentTransaction::where('gateway_transaction_id', $result->gatewayTransactionId)->first();
        }

        if ($txn) {
            $txn->status = 'completed';
            $txn->gateway_transaction_id = $result->gatewayTransactionId ?: $txn->gateway_transaction_id;
            $txn->is_reconciled = true;
            $txn->reconciled_at = now();
            $txn->save();

            // Create invoice if not yet generated
            if (! $txn->invoice) {
                $this->invoiceService->createFromTransaction($txn);
            }

            // If transaction has associated listing, activate featured listing if pending
            if ($txn->listing_id) {
                $featured = FeaturedListing::where('listing_id', $txn->listing_id)
                    ->where('user_id', $txn->user_id)
                    ->first();

                if ($featured && ! $featured->is_active) {
                    $featured->is_active = true;
                    $featured->status = 'active';
                    $featured->save();

                    $txn->listing->is_featured = true;
                    $txn->listing->promoted_until = $featured->ends_at;
                    $txn->listing->save();
                }
            }
        }
    }

    protected function handleChargeFailed($result): void
    {
        $txn = null;
        if ($result->transactionReference) {
            $txn = PaymentTransaction::where('reference_id', $result->transactionReference)->first();
        }

        if ($txn) {
            $txn->status = 'failed';
            $txn->save();
        }
    }

    protected function handleChargeRefunded($result): void
    {
        $txn = null;
        if ($result->gatewayTransactionId) {
            $txn = PaymentTransaction::where('gateway_transaction_id', $result->gatewayTransactionId)->first();
        }
        if (! $txn && $result->transactionReference) {
            $txn = PaymentTransaction::where('reference_id', $result->transactionReference)->first();
        }

        if ($txn && $txn->status !== 'refunded') {
            $txn->status = 'refunded';
            $txn->save();

            // Create refund record
            Refund::create([
                'payment_transaction_id' => $txn->id,
                'processed_by_admin_id' => $txn->user_id, // System recorded via webhook
                'amount_cents' => $result->amountCents ?: $txn->amount_cents,
                'reason' => 'Gateway webhook refund event',
                'status' => 'completed',
                'gateway_refund_id' => $result->eventId,
            ]);

            // Update invoice status if present
            if ($txn->invoice) {
                $txn->invoice->status = 'refunded';
                $txn->invoice->save();
            }

            // Deactivate featured listing if applicable
            if ($txn->listing_id) {
                FeaturedListing::where('listing_id', $txn->listing_id)
                    ->where('user_id', $txn->user_id)
                    ->update(['is_active' => false, 'status' => 'deactivated']);

                $txn->listing->is_featured = false;
                $txn->listing->promoted_until = null;
                $txn->listing->save();
            }
        }
    }

    protected function handleSubscriptionRenewed($result): void
    {
        if ($result->subscriptionId) {
            $sub = Subscription::where('gateway_subscription_id', $result->subscriptionId)->first();
            if ($sub && $sub->plan) {
                $days = $sub->plan->duration_days ?: 30;
                $sub->ends_at = ($sub->ends_at ?: now())->addDays($days);
                $sub->next_renewal_at = $sub->ends_at;
                $sub->status = 'active';
                $sub->save();
            }
        }
    }

    protected function handleSubscriptionCanceled($result): void
    {
        if ($result->subscriptionId) {
            $sub = Subscription::where('gateway_subscription_id', $result->subscriptionId)->first();
            if ($sub) {
                $sub->status = 'canceled';
                $sub->canceled_at = now();
                $sub->auto_renew = false;
                $sub->save();
            }
        }
    }
}
