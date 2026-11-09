<?php

namespace App\Modules\Payments\Crypto\Services;

use App\Modules\Payments\Crypto\Gateways\CryptoGatewayManager;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Models\PaymentGateway;
use App\Modules\Payments\Crypto\Models\PaymentWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CryptoWebhookProcessor
{
    public function __construct(
        protected CryptoGatewayManager $gatewayManager,
        protected ListingBoostService $boostService
    ) {}

    /**
     * Process an inbound webhook from a crypto payment gateway.
     */
    public function process(string $providerSlug, Request $request): array
    {
        $gateway = PaymentGateway::where('provider', $providerSlug)
            ->orWhere('slug', $providerSlug)
            ->first();

        if (!$gateway) {
            Log::warning("Crypto webhook received for unknown provider: {$providerSlug}");
            return ['status' => 404, 'message' => 'Payment gateway provider not found'];
        }

        $adapter = $this->gatewayManager->get($gateway);

        // 1. Record raw webhook event immediately for complete security audit trail
        $payload = $request->all();
        $headers = $request->headers->all();

        // 2. Cryptographic signature verification
        $signatureVerified = $adapter->verifyWebhook($request, $gateway);

        $webhookEvent = PaymentWebhookEvent::create([
            'gateway_id' => $gateway->id,
            'event_type' => $payload['event'] ?? $payload['status'] ?? 'payment_notification',
            'payload' => $payload,
            'headers' => $headers,
            'signature_verified' => $signatureVerified,
            'processing_status' => $signatureVerified ? 'pending' : 'failed',
            'error_message' => $signatureVerified ? null : 'Invalid cryptographic webhook signature',
        ]);

        if (!$signatureVerified) {
            Log::securityAlert('Invalid crypto webhook signature detected', [
                'provider' => $providerSlug,
                'ip' => $request->ip(),
                'event_id' => $webhookEvent->id,
            ]);
            return ['status' => 401, 'message' => 'Invalid webhook signature'];
        }

        // 3. Parse and process payload with provider adapter
        $result = $adapter->handleWebhook($request, $gateway);

        if (!$result->success) {
            $webhookEvent->update([
                'processing_status' => 'failed',
                'error_message' => $result->errorMessage,
            ]);
            return ['status' => 400, 'message' => $result->errorMessage];
        }

        // 4. Locate payment record by internal order ID or provider payment ID
        $payment = null;
        if (!empty($result->internalOrderId)) {
            $payment = CryptoPayment::where('internal_order_id', $result->internalOrderId)->first();
        }
        if (!$payment && !empty($result->providerPaymentId)) {
            $payment = CryptoPayment::where('provider_payment_id', $result->providerPaymentId)->first();
        }

        if (!$payment) {
            Log::warning("Crypto payment not found for webhook order: {$result->internalOrderId}", [
                'provider' => $providerSlug,
                'result' => (array) $result,
            ]);
            $webhookEvent->update([
                'processing_status' => 'ignored',
                'error_message' => 'Payment record not found matching order ID',
            ]);
            return ['status' => 200, 'message' => 'Payment record not found, acknowledged'];
        }

        $webhookEvent->update(['crypto_payment_id' => $payment->id]);

        // 5. Update payment details & state idempotently
        return DB::transaction(function () use ($payment, $result, $webhookEvent) {
            $lockedPayment = CryptoPayment::where('id', $payment->id)->lockForUpdate()->first();

            // Replay / Duplicate verification check: if already completed, acknowledge idempotently
            if ($lockedPayment->status === CryptoPayment::STATUS_COMPLETED) {
                $webhookEvent->update([
                    'processing_status' => 'duplicate',
                    'processed_at' => now(),
                ]);
                return ['status' => 200, 'message' => 'Payment already completed, acknowledged'];
            }

            // Update received amounts and transaction hash if provided
            if ($result->receivedAmount !== null) {
                $lockedPayment->received_amount = (float) $result->receivedAmount;
            }
            if (!empty($result->transactionHash)) {
                $lockedPayment->transaction_hash = $result->transactionHash;
            }
            if ($result->confirmations > 0) {
                $lockedPayment->confirmations = $result->confirmations;
            }

            // Check for underpayment policy
            if ($result->status === CryptoPayment::STATUS_COMPLETED && $lockedPayment->expected_amount > 0) {
                // If received amount is specified and significantly less than expected (threshold 98%)
                if ($lockedPayment->received_amount > 0 && $lockedPayment->received_amount < ($lockedPayment->expected_amount * 0.98)) {
                    $lockedPayment->status = CryptoPayment::STATUS_UNDERPAID;
                    $lockedPayment->save();
                    $webhookEvent->update([
                        'processing_status' => 'failed',
                        'error_message' => 'Received amount is less than expected amount (underpaid)',
                        'processed_at' => now(),
                    ]);
                    return ['status' => 200, 'message' => 'Underpayment detected, awaiting top-up'];
                }
            }

            $lockedPayment->status = $result->status;
            $lockedPayment->save();

            // 6. If payment is completed: automatically activate listing boost or credit wallet
            if ($lockedPayment->status === CryptoPayment::STATUS_COMPLETED) {
                if ($lockedPayment->payment_type === 'credit_package' && $lockedPayment->credit_package_id) {
                    $package = \App\Modules\Monetization\Models\CreditPackage::find($lockedPayment->credit_package_id);
                    if ($package && $lockedPayment->user) {
                        $totalCredits = $package->credits + $package->bonus_credits;
                        app(\App\Modules\Monetization\Services\UserWalletService::class)->credit(
                            $lockedPayment->user,
                            $totalCredits,
                            \App\Modules\Monetization\Models\WalletTransaction::TYPE_CREDIT_PURCHASE,
                            "Purchased {$package->name} ({$package->credits} credits" . ($package->bonus_credits > 0 ? " + {$package->bonus_credits} bonus" : '') . ")",
                            null,
                            $lockedPayment->id,
                            "crypto_credit_pkg_{$lockedPayment->id}"
                        );
                    }
                } else {
                    $this->boostService->activateBoost($lockedPayment);
                }
            }

            $webhookEvent->update([
                'processing_status' => 'processed',
                'processed_at' => now(),
            ]);

            return ['status' => 200, 'message' => 'Webhook processed successfully'];
        });
    }
}

