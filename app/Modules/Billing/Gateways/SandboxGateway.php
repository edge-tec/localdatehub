<?php

namespace App\Modules\Billing\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SandboxGateway implements PaymentGatewayInterface
{
    protected string $webhookSecret;

    public function __construct(array $config = [])
    {
        $this->webhookSecret = $config['webhook_secret'] ?? config('monetization.gateways.sandbox.webhook_secret', 'sandbox_webhook_secret_key_12345');
    }

    public function getIdentifier(): string
    {
        return 'sandbox';
    }

    public function getName(): string
    {
        return 'Sandbox Simulation Gateway';
    }

    public function createCharge(array $params): ChargeResult
    {
        $amountCents = (int) ($params['amount_cents'] ?? 0);
        $cardToken = $params['payment_token'] ?? $params['token'] ?? 'tok_sandbox_default';
        $cardLastFour = $params['card_last_four'] ?? substr($cardToken, -4) ?: '4242';
        $cardBrand = $params['card_brand'] ?? 'Visa';

        // Simulated failure cases
        if ($cardToken === 'tok_charge_declined' || $cardLastFour === '0002') {
            return new ChargeResult(
                success: false,
                referenceId: $params['reference_id'] ?? null,
                amountCents: $amountCents,
                currency: $params['currency'] ?? 'USD',
                errorMessage: 'Your card was declined by the issuer (simulated).',
                rawPayload: ['simulated_decline' => true]
            );
        }

        if ($cardToken === 'tok_insufficient_funds' || $cardLastFour === '9999') {
            return new ChargeResult(
                success: false,
                referenceId: $params['reference_id'] ?? null,
                amountCents: $amountCents,
                currency: $params['currency'] ?? 'USD',
                errorMessage: 'Insufficient funds available in account.',
                rawPayload: ['simulated_insufficient_funds' => true]
            );
        }

        $txnId = 'sbx_txn_' . Str::lower(Str::random(16));

        return new ChargeResult(
            success: true,
            transactionId: $txnId,
            referenceId: $params['reference_id'] ?? 'REF-' . strtoupper(Str::random(10)),
            amountCents: $amountCents,
            currency: $params['currency'] ?? 'USD',
            cardLastFour: $cardLastFour,
            cardBrand: $cardBrand,
            rawPayload: [
                'gateway' => 'sandbox',
                'gateway_transaction_id' => $txnId,
                'created_at' => now()->toIso8601String(),
                'status' => 'settled',
            ]
        );
    }

    public function createSubscription(array $params): SubscriptionResult
    {
        $planId = $params['plan_id'] ?? 'default_plan';
        $subId = 'sbx_sub_' . Str::lower(Str::random(16));

        return new SubscriptionResult(
            success: true,
            subscriptionId: $subId,
            status: 'active',
            rawPayload: [
                'gateway' => 'sandbox',
                'plan_id' => $planId,
                'created_at' => now()->toIso8601String(),
            ]
        );
    }

    public function cancelSubscription(string $gatewaySubscriptionId): bool
    {
        return true;
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Gateway-Signature') ?? $request->header('X-Sandbox-Signature');
        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        $data = $request->json()->all();

        $eventId = $data['event_id'] ?? $data['id'] ?? 'evt_' . Str::random(12);
        $eventType = $data['event_type'] ?? $data['type'] ?? 'charge.succeeded';
        $reference = $data['reference_id'] ?? ($data['data']['reference_id'] ?? null);
        $txnId = $data['transaction_id'] ?? ($data['data']['transaction_id'] ?? null);
        $subId = $data['subscription_id'] ?? ($data['data']['subscription_id'] ?? null);
        $amount = (int) ($data['amount_cents'] ?? ($data['data']['amount_cents'] ?? 0));

        return new WebhookResult(
            valid: true,
            eventId: $eventId,
            eventType: $eventType,
            transactionReference: $reference,
            gatewayTransactionId: $txnId,
            subscriptionId: $subId,
            amountCents: $amount,
            data: $data
        );
    }

    public function refund(string $gatewayTransactionId, int $amountCents, string $reason): RefundResult
    {
        $refundId = 'sbx_ref_' . Str::lower(Str::random(16));

        return new RefundResult(
            success: true,
            refundId: $refundId,
            amountCents: $amountCents,
            rawPayload: [
                'gateway' => 'sandbox',
                'original_transaction_id' => $gatewayTransactionId,
                'refund_id' => $refundId,
                'reason' => $reason,
                'created_at' => now()->toIso8601String(),
            ]
        );
    }
}