<?php

namespace App\Modules\Payments\Crypto\Gateways;

use App\Modules\Payments\Crypto\DTOs\GatewayPaymentResponse;
use App\Modules\Payments\Crypto\DTOs\GatewayStatusResponse;
use App\Modules\Payments\Crypto\DTOs\GatewayTestResult;
use App\Modules\Payments\Crypto\DTOs\WebhookProcessingResult;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Models\PaymentGateway;
use Illuminate\Http\Request;

interface CryptoPaymentGatewayInterface
{
    /**
     * Get unique provider identifier slug (e.g. nowpayments, coinpayments, etc.).
     */
    public function getIdentifier(): string;

    /**
     * Get human-readable provider name.
     */
    public function getName(): string;

    /**
     * Create an invoice / payment address with the provider.
     */
    public function createPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayPaymentResponse;

    /**
     * Query provider API directly to verify payment status.
     */
    public function verifyPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayStatusResponse;

    /**
     * Query provider API status by provider payment ID.
     */
    public function getPaymentStatus(string $providerPaymentId, PaymentGateway $gateway): GatewayStatusResponse;

    /**
     * Verify cryptographic webhook signature.
     */
    public function verifyWebhook(Request $request, PaymentGateway $gateway): bool;

    /**
     * Parse and process provider-specific webhook payload.
     */
    public function handleWebhook(Request $request, PaymentGateway $gateway): WebhookProcessingResult;

    /**
     * Get list of supported cryptocurrency symbols for this provider.
     */
    public function getSupportedCurrencies(): array;

    /**
     * Get list of supported networks for a given currency.
     */
    public function getSupportedNetworks(string $currency): array;

    /**
     * Process refund where supported by provider API.
     */
    public function refundPayment(CryptoPayment $payment, PaymentGateway $gateway, string $reason): bool;

    /**
     * Test API connectivity and validate credentials.
     */
    public function testConnection(PaymentGateway $gateway): GatewayTestResult;
}

