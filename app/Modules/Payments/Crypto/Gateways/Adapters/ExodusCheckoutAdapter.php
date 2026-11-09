<?php

namespace App\Modules\Payments\Crypto\Gateways\Adapters;

use App\Modules\Payments\Crypto\DTOs\GatewayPaymentResponse;
use App\Modules\Payments\Crypto\DTOs\GatewayStatusResponse;
use App\Modules\Payments\Crypto\DTOs\GatewayTestResult;
use App\Modules\Payments\Crypto\DTOs\WebhookProcessingResult;
use App\Modules\Payments\Crypto\Gateways\CryptoPaymentGatewayInterface;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Models\PaymentGateway;
use App\Modules\Payments\Crypto\Support\CryptoCurrencyRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExodusCheckoutAdapter implements CryptoPaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'exodus';
    }

    public function getName(): string
    {
        return 'Exodus Checkout';
    }

    protected function getBaseUrl(PaymentGateway $gateway): string
    {
        return $gateway->environment === 'live'
            ? 'https://checkout.exodus.com/api/v1'
            : 'https://sandbox.checkout.exodus.com/api/v1';
    }

    public function createPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayPaymentResponse
    {
        $credentials = $gateway->getCredentials();
        $apiKey = $credentials['api_key'] ?? '';

        if (empty($apiKey)) {
            return GatewayPaymentResponse::failed('Exodus Checkout API Key is not configured.');
        }

        $endpoint = $this->getBaseUrl($gateway) . '/invoices';

        $payload = [
            'order_id' => $payment->internal_order_id,
            'amount' => (float) $payment->fiat_amount,
            'currency' => strtoupper($payment->fiat_currency),
            'pay_currency' => strtoupper($payment->payment_currency),
            'network' => strtoupper($payment->payment_network),
            'item_title' => "Boost: {$payment->boostPlan?->name}",
            'callback_url' => url('/webhooks/crypto/exodus'),
            'redirect_url' => route('account.listings.index', ['boost_success' => 1]),
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $invoiceId = (string) ($data['id'] ?? $data['invoice_id'] ?? '');
                $address = $data['address'] ?? $data['payment_address'] ?? null;
                $paymentUrl = $data['checkout_url'] ?? $data['url'] ?? null;
                $expectedAmount = (string) ($data['pay_amount'] ?? $data['crypto_amount'] ?? $payment->expected_amount);
                $qrCode = $address ? CryptoCurrencyRegistry::buildQrCodeUri($payment->payment_currency, $payment->payment_network, $address, $expectedAmount) : $paymentUrl;

                $expiresAt = now()->addHours(1);
                if (!empty($data['expires_at'])) {
                    try {
                        $expiresAt = new \DateTimeImmutable($data['expires_at']);
                    } catch (\Throwable $e) {}
                }

                return GatewayPaymentResponse::successful(
                    providerPaymentId: $invoiceId,
                    paymentAddress: $address,
                    paymentUrl: $paymentUrl,
                    expectedAmount: $expectedAmount,
                    qrCodeData: $qrCode,
                    providerInvoiceId: $invoiceId,
                    expiresAt: $expiresAt,
                    rawResponse: $data
                );
            }

            return GatewayPaymentResponse::failed('Exodus Checkout Error: ' . $response->body(), $response->json() ?? []);
        } catch (\Throwable $e) {
            Log::error('Exodus Checkout createPayment exception', ['error' => $e->getMessage()]);
            return GatewayPaymentResponse::failed('Exodus connection error: ' . $e->getMessage());
        }
    }

    public function verifyPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayStatusResponse
    {
        if (empty($payment->provider_payment_id)) {
            return GatewayStatusResponse::failed('Missing Exodus invoice ID.');
        }

        return $this->getPaymentStatus($payment->provider_payment_id, $gateway);
    }

    public function getPaymentStatus(string $providerPaymentId, PaymentGateway $gateway): GatewayStatusResponse
    {
        $credentials = $gateway->getCredentials();
        $apiKey = $credentials['api_key'] ?? '';

        if (empty($apiKey)) {
            return GatewayStatusResponse::failed('Exodus API key not configured.');
        }

        $endpoint = $this->getBaseUrl($gateway) . "/invoices/{$providerPaymentId}";

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => "Bearer {$apiKey}"])
                ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                $exStatus = strtolower($data['status'] ?? '');
                $appStatus = $this->mapExodusStatus($exStatus);

                return GatewayStatusResponse::successful(
                    status: $appStatus,
                    receivedAmount: isset($data['paid_amount']) ? (string) $data['paid_amount'] : null,
                    transactionHash: $data['tx_hash'] ?? null,
                    confirmations: (int) ($data['confirmations'] ?? 0),
                    rawResponse: $data
                );
            }

            return GatewayStatusResponse::failed('Failed to retrieve Exodus status: ' . $response->body());
        } catch (\Throwable $e) {
            return GatewayStatusResponse::failed('Exodus query exception: ' . $e->getMessage());
        }
    }

    public function verifyWebhook(Request $request, PaymentGateway $gateway): bool
    {
        $secret = $gateway->getWebhookSecret();
        if (empty($secret)) {
            Log::warning('Exodus webhook secret not configured.');
            return false;
        }

        $receivedSig = $request->header('x-exodus-signature');
        if (empty($receivedSig)) {
            return false;
        }

        $content = $request->getContent();
        $expectedSig = hash_hmac('sha256', $content, $secret);

        return hash_equals($expectedSig, $receivedSig);
    }

    public function handleWebhook(Request $request, PaymentGateway $gateway): WebhookProcessingResult
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;
        $invoiceId = isset($payload['id']) ? (string) $payload['id'] : null;
        $exStatus = strtolower($payload['status'] ?? '');
        $status = $this->mapExodusStatus($exStatus);

        return WebhookProcessingResult::successful(
            internalOrderId: $orderId,
            providerPaymentId: $invoiceId,
            status: $status,
            receivedAmount: isset($payload['paid_amount']) ? (string) $payload['paid_amount'] : null,
            transactionHash: $payload['tx_hash'] ?? null,
            confirmations: (int) ($payload['confirmations'] ?? 1),
            rawPayload: $payload
        );
    }

    public function getSupportedCurrencies(): array
    {
        return ['BTC', 'ETH', 'USDT', 'USDC', 'SOL', 'LTC', 'XRP', 'DOGE'];
    }

    public function getSupportedNetworks(string $currency): array
    {
        return array_keys(CryptoCurrencyRegistry::getNetworksForCurrency($currency));
    }

    public function refundPayment(CryptoPayment $payment, PaymentGateway $gateway, string $reason): bool
    {
        Log::info("Refund requested for Exodus payment {$payment->internal_order_id}: {$reason}");
        return false;
    }

    public function testConnection(PaymentGateway $gateway): GatewayTestResult
    {
        $credentials = $gateway->getCredentials();
        $apiKey = $credentials['api_key'] ?? '';

        if (empty($apiKey)) {
            return GatewayTestResult::failed('Exodus API Key is missing.');
        }

        $endpoint = $this->getBaseUrl($gateway) . '/ping';

        try {
            $response = Http::timeout(8)
                ->withHeaders(['Authorization' => "Bearer {$apiKey}"])
                ->get($endpoint);

            if ($response->successful()) {
                return GatewayTestResult::passed('Exodus Checkout API ping succeeded.', $response->json() ?? []);
            }

            return GatewayTestResult::failed('Exodus Checkout returned HTTP ' . $response->status());
        } catch (\Throwable $e) {
            return GatewayTestResult::failed('Exodus connection error: ' . $e->getMessage());
        }
    }

    protected function mapExodusStatus(string $status): string
    {
        return match ($status) {
            'confirmed', 'paid', 'complete' => CryptoPayment::STATUS_COMPLETED,
            'confirming' => CryptoPayment::STATUS_CONFIRMING,
            'pending' => CryptoPayment::STATUS_AWAITING_PAYMENT,
            'expired' => CryptoPayment::STATUS_EXPIRED,
            'failed', 'canceled' => CryptoPayment::STATUS_FAILED,
            'refunded' => CryptoPayment::STATUS_REFUNDED,
            default => CryptoPayment::STATUS_PENDING,
        };
    }
}

