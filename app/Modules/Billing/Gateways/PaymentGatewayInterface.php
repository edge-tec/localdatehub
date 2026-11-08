<?php

namespace App\Modules\Billing\Gateways;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Get the gateway identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get display name.
     */
    public function getName(): string;

    /**
     * Charge a customer for a single transaction (listing promotion, ad slot, bump).
     */
    public function createCharge(array $params): ChargeResult;

    /**
     * Create a recurring subscription.
     */
    public function createSubscription(array $params): SubscriptionResult;

    /**
     * Cancel an active gateway subscription.
     */
    public function cancelSubscription(string $gatewaySubscriptionId): bool;

    /**
     * Verify the HMAC or webhook signature from the provider.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Parse and handle the inbound webhook payload.
     */
    public function parseWebhook(Request $request): WebhookResult;

    /**
     * Process a refund for a previous transaction.
     */
    public function refund(string $gatewayTransactionId, int $amountCents, string $reason): RefundResult;
}
