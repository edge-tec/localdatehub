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

class CoinGateAdapter implements CryptoPaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'coingate';
    }

    public function getName(): string
    {
        return 'CoinGate';
    }

    protected function getBaseUrl(PaymentGateway $gateway): string
    {
        return $gateway->environment === 'live'
            ? 'https://api.coingate.com/v2'
            : 'https://api-sandbox.coingate.com/v2';
    }

    public function createPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayPaymentResponse
    {
        $credentials = $gateway->getCredentials();
        $apiToken = $credentials['api_token'] ?? '';

        if (empty($apiToken)) {
            return GatewayPaymentResponse::failed('CoinGate API token is not configured.');
        }

        $endpoint = $this->getBaseUrl($gateway) . '/orders';

        $payload = [
            'order_id' => $payment->internal_order_id,
            'price_amount' => (float) $payment->fiat_amount,
            'price_currency' => strtoupper($payment->fiat_currency),
            'receive_currency' => strtoupper($payment->payment_currency),
            'title' => "Boost Plan: {$payment->boostPlan?->name}",
            'description' => "Listing #{$payment->listing_id} Promotion",
            'callback_url' => url('/webhooks/crypto/coingate'),
            'cancel_url' => route('account.listings.index'),
            'success_url' => route('account.listings.index', ['boost_success' => 1]),
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiToken}",
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $orderId = (string) ($data['id'] ?? '');
                $paymentUrl = $data['payment_url'] ?? null;
                $address = $data['payment_address'] ?? null;
                $expectedAmount = (string) ($data['pay_amount'] ?? $payment->expected_amount);
                $qrCode = $address ? CryptoCurrencyRegistry::buildQrCodeUri($payment->payment_currency, $payment->payment_network, $address, $expectedAmount) : $paymentUrl;

                $expiresAt = now()->addHours(1);
                if (!empty($data['expire_at'])) {
                    try {
                        $expiresAt = new \DateTimeImmutable($data['expire_at']);
                    } catch (\Throwable $e) {}
                }

                return GatewayPaymentResponse::successful(
                    providerPaymentId: $orderId,
                    paymentAddress: $address,
                    paymentUrl: $paymentUrl,
                    expectedAmount: $expectedAmount,
                    qrCodeData: $qrCode,
                    providerInvoiceId: $orderId,
                    expiresAt: $expiresAt,
                    rawResponse: $data
                );
            }

            return GatewayPaymentResponse::failed(
                'CoinGate Error: ' . ($response->json('message') ?? $response->body()),
                $response->json() ?? []
            );
        } catch (\Throwable $e) {
            Log::error('CoinGate createPayment exception', ['error' => $e->getMessage()]);
            return GatewayPaymentResponse::failed('CoinGate connection error: ' . $e->getMessage());
        }
    }

    public function verifyPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayStatusResponse
    {
        if (empty($payment->provider_payment_id)) {
            return GatewayStatusResponse::failed('Missing CoinGate order ID.');
        }

        return $this->getPaymentStatus($payment->provider_payment_id, $gateway);
    }

    public function getPaymentStatus(string $providerPaymentId, PaymentGateway $gateway): GatewayStatusResponse
    {
        $credentials = $gateway->getCredentials();
        $apiToken = $credentials['api_token'] ?? '';

        if (empty($apiToken)) {
            return GatewayStatusResponse::failed('CoinGate API token missing.');
        }

        $endpoint = $this->getBaseUrl($gateway) . "/orders/{$providerPaymentId}";

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => "Bearer {$apiToken}"])
                ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                $cgStatus = strtolower($data['status'] ?? '');
                $appStatus = $this->mapCoinGateStatus($cgStatus);

                return GatewayStatusResponse::successful(
                    status: $appStatus,
                    receivedAmount: isset($data['pay_amount']) ? (string) $data['pay_amount'] : null,
                    rawResponse: $data
                );
            }

            return GatewayStatusResponse::failed('CoinGate status query error: ' . $response->body());
        } catch (\Throwable $e) {
            return GatewayStatusResponse::failed('Status exception: ' . $e->getMessage());
        }
    }

    public function verifyWebhook(Request $request, PaymentGateway $gateway): bool
    {
        $secret = $gateway->getWebhookSecret();
        
        // If a shared token or secret is configured in CoinGate webhook settings
        if (!empty($secret)) {
            $incomingToken = $request->header('Authorization') ?: $request->input('token');
            if (!empty($incomingToken)) {
                $tokenClean = str_replace('Bearer ', '', $incomingToken);
                return hash_equals($secret, $tokenClean);
            }
        }

        // Validate that request contains expected CoinGate order structure
        return $request->filled('id') && $request->filled('status');
    }

    public function handleWebhook(Request $request, PaymentGateway $gateway): WebhookProcessingResult
    {
        $payload = $request->all();
        $internalOrderId = $payload['order_id'] ?? null;
        $orderId = isset($payload['id']) ? (string) $payload['id'] : null;
        $cgStatus = strtolower($payload['status'] ?? '');
        $status = $this->mapCoinGateStatus($cgStatus);

        return WebhookProcessingResult::successful(
            internalOrderId: $internalOrderId,
            providerPaymentId: $orderId,
            status: $status,
            receivedAmount: isset($payload['pay_amount']) ? (string) $payload['pay_amount'] : null,
            rawPayload: $payload
        );
    }

    public function getSupportedCurrencies(): array
    {
        return ['BTC', 'ETH', 'USDT', 'USDC', 'LTC', 'TRX', 'BNB', 'DOGE', 'BCH', 'XRP'];
    }

    public function getSupportedNetworks(string $currency): array
    {
        return array_keys(CryptoCurrencyRegistry::getNetworksForCurrency($currency));
    }

    public function refundPayment(CryptoPayment $payment, PaymentGateway $gateway, string $reason): bool
    {
        Log::info("Refund requested for CoinGate payment {$payment->internal_order_id}: {$reason}");
        return false;
    }

    public function testConnection(PaymentGateway $gateway): GatewayTestResult
    {
        $credentials = $gateway->getCredentials();
        $apiToken = $credentials['api_token'] ?? '';

        if (empty($apiToken)) {
            return GatewayTestResult::failed('CoinGate API token is missing.');
        }

        $endpoint = $this->getBaseUrl($gateway) . '/ping';

        try {
            $response = Http::timeout(8)
                ->withHeaders(['Authorization' => "Bearer {$apiToken}"])
                ->get($endpoint);

            if ($response->successful()) {
                return GatewayTestResult::passed('CoinGate API ping succeeded and token is authenticated.', $response->json() ?? []);
            }

            return GatewayTestResult::failed('CoinGate ping failed with HTTP ' . $response->status());
        } catch (\Throwable $e) {
            return GatewayTestResult::failed('Failed to ping CoinGate API: ' . $e->getMessage());
        }
    }

    protected function mapCoinGateStatus(string $status): string
    {
        return match ($status) {
            'paid' => CryptoPayment::STATUS_COMPLETED,
            'confirming' => CryptoPayment::STATUS_CONFIRMING,
            'pending' => CryptoPayment::STATUS_AWAITING_PAYMENT,
            'expired' => CryptoPayment::STATUS_EXPIRED,
            'canceled' => CryptoPayment::STATUS_CANCELLED,
            'refunded' => CryptoPayment::STATUS_REFUNDED,
            default => CryptoPayment::STATUS_PENDING,
        };
    }
}

