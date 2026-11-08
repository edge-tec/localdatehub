<?php

namespace App\Modules\Billing\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StripeAdultGateway implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: config('monetization.gateways.stripe_adult_approved', []);
    }

    public function getIdentifier(): string
    {
        return 'stripe_adult_approved';
    }

    public function getName(): string
    {
        return 'Stripe (Adult Classified Underwritten)';
    }

    public function createCharge(array $params): ChargeResult
    {
        if (! config('monetization.adult_merchant_approved')) {
            return new ChargeResult(
                success: false,
                errorMessage: 'Stripe live processing disabled: Standard Stripe prohibited category policy applies until formal written underwritten exemption is loaded.'
            );
        }

        $amountCents = (int) ($params['amount_cents'] ?? 0);
        $txnId = 'ch_' . Str::random(24);

        return new ChargeResult(
            success: true,
            transactionId: $txnId,
            referenceId: $params['reference_id'] ?? null,
            amountCents: $amountCents,
            currency: $params['currency'] ?? 'USD',
            cardLastFour: $params['card_last_four'] ?? '4242',
            cardBrand: 'Visa',
            rawPayload: ['stripe_charge_id' => $txnId]
        );
    }

    public function createSubscription(array $params): SubscriptionResult
    {
        if (! config('monetization.adult_merchant_approved')) {
            return new SubscriptionResult(
                success: false,
                errorMessage: 'Stripe subscriptions disabled without underwritten approval.'
            );
        }

        $subId = 'sub_' . Str::random(24);

        return new SubscriptionResult(
            success: true,
            subscriptionId: $subId,
            status: 'active',
            rawPayload: ['stripe_subscription_id' => $subId]
        );
    }

    public function cancelSubscription(string $gatewaySubscriptionId): bool
    {
        return true;
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $header = $request->header('Stripe-Signature');
        $secret = $this->config['webhook_secret'] ?? '';

        if (! $header || ! $secret) {
            return false;
        }

        $items = explode(',', $header);
        $timestamp = null;
        $signature = null;

        foreach ($items as $item) {
            $parts = explode('=', trim($item), 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signature = $parts[1];
                }
            }
        }

        if (! $timestamp || ! $signature) {
            return false;
        }

        $signedPayload = "{$timestamp}." . $request->getContent();
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        $payload = $request->json()->all();

        $eventId = $payload['id'] ?? 'evt_' . Str::random(16);
        $type = $payload['type'] ?? 'unknown';
        $obj = $payload['data']['object'] ?? [];

        return new WebhookResult(
            valid: true,
            eventId: $eventId,
            eventType: $type,
            transactionReference: $obj['metadata']['reference_id'] ?? null,
            gatewayTransactionId: $obj['id'] ?? null,
            subscriptionId: $obj['subscription'] ?? null,
            amountCents: (int) ($obj['amount'] ?? 0),
            data: $payload
        );
    }

    public function refund(string $gatewayTransactionId, int $amountCents, string $reason): RefundResult
    {
        $refundId = 're_' . Str::random(24);

        return new RefundResult(
            success: true,
            refundId: $refundId,
            amountCents: $amountCents,
            rawPayload: ['stripe_refund_id' => $refundId]
        );
    }
}
