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

class BitPayAdapter implements CryptoPaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'bitpay';
    }

    public function getName(): string
    {
        return 'BitPay';
    }

    protected function getBaseUrl(PaymentGateway $gateway): string
    {
        return $gateway->environment === 'live'
            ? 'https://bitpay.com'
            : 'https://test.bitpay.com';
    }

    public function createPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayPaymentResponse
    {
        $credentials = $gateway->getCredentials();
        $token = $credentials['api_token'] ?? '';

        if (empty($token)) {
            return GatewayPaymentResponse::failed('BitPay API token is not configured.');
        }

        $endpoint = $this->getBaseUrl($gateway) . '/invoices';

        $payload = [
            'price' => (float) $payment->fiat_amount,
            'currency' => strtoupper($payment->fiat_currency),
            'orderId' => $payment->internal_order_id,
            'itemDesc' => "Boost Plan: {$payment->boostPlan?->name}",
            'notificationURL' => url('/webhooks/crypto/bitpay'),
            'redirectURL' => route('account.listings.index', ['boost_success' => 1]),
            'token' => $token,
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'x-accept-version' => '2.0.0',
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['data'] ?? $body;
                $invoiceId = (string) ($data['id'] ?? '');
                $paymentUrl = $data['url'] ?? null;
                $expectedAmount = (string) ($data['price'] ?? $payment->expected_amount);

                $expiresAt = now()->addMinutes(15);
                if (!empty($data['expirationTime'])) {
                    $expiresAt = (new \DateTimeImmutable())->setTimestamp((int) ($data['expirationTime'] / 1000));
                }

                return GatewayPaymentResponse::successful(
                    providerPaymentId: $invoiceId,
                    paymentAddress: null,
                    paymentUrl: $paymentUrl,
                    expectedAmount: $expectedAmount,
                    qrCodeData: $paymentUrl,
                    providerInvoiceId: $invoiceId,
                    expiresAt: $expiresAt,
                    rawResponse: $body
                );
            }

            return GatewayPaymentResponse::failed('BitPay Error: ' . $response->body(), $response->json() ?? []);
        } catch (\Throwable $e) {
            Log::error('BitPay createPayment exception', ['error' => $e->getMessage()]);
            return GatewayPaymentResponse::failed('BitPay connection error: ' . $e->getMessage());
        }
    }

    public function verifyPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayStatusResponse
    {
        if (empty($payment->provider_payment_id)) {
            return GatewayStatusResponse::failed('Missing BitPay invoice ID.');
        }

        return $this->getPaymentStatus($payment->provider_payment_id, $gateway);
    }

    public function getPaymentStatus(string $providerPaymentId, PaymentGateway $gateway): GatewayStatusResponse
    {
        $credentials = $gateway->getCredentials();
        $token = $credentials['api_token'] ?? '';

        if (empty($token)) {
            return GatewayStatusResponse::failed('BitPay token not configured.');
        }

        $endpoint = $this->getBaseUrl($gateway) . "/invoices/{$providerPaymentId}?token={$token}";

        try {
            $response = Http::timeout(10)
                ->withHeaders(['x-accept-version' => '2.0.0'])
                ->get($endpoint);

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['data'] ?? $body;
                $bitStatus = strtolower($data['status'] ?? '');
                $appStatus = $this->mapBitPayStatus($bitStatus);

                return GatewayStatusResponse::successful(
                    status: $appStatus,
                    rawResponse: $body
                );
            }

            return GatewayStatusResponse::failed('Failed to retrieve BitPay status: ' . $response->body());
        } catch (\Throwable $e) {
            return GatewayStatusResponse::failed('BitPay status exception: ' . $e->getMessage());
        }
    }

    public function verifyWebhook(Request $request, PaymentGateway $gateway): bool
    {
        $payload = $request->json('data') ?? $request->all();
        return !empty($payload['id']) && !empty($payload['status']);
    }

    public function handleWebhook(Request $request, PaymentGateway $gateway): WebhookProcessingResult
    {
        $data = $request->json('data') ?? $request->all();
        $internalOrderId = $data['orderId'] ?? null;
        $invoiceId = isset($data['id']) ? (string) $data['id'] : null;
        $bitStatus = strtolower($data['status'] ?? '');
        $status = $this->mapBitPayStatus($bitStatus);

        return WebhookProcessingResult::successful(
            internalOrderId: $internalOrderId,
            providerPaymentId: $invoiceId,
            status: $status,
            rawPayload: $request->all()
        );
    }

    public function getSupportedCurrencies(): array
    {
        return ['BTC', 'ETH', 'USDT', 'USDC', 'LTC', 'DOGE', 'BCH', 'XRP'];
    }

    public function getSupportedNetworks(string $currency): array
    {
        return array_keys(CryptoCurrencyRegistry::getNetworksForCurrency($currency));
    }

    public function refundPayment(CryptoPayment $payment, PaymentGateway $gateway, string $reason): bool
    {
        Log::info("Refund requested for BitPay payment {$payment->internal_order_id}: {$reason}");
        return false;
    }

    public function testConnection(PaymentGateway $gateway): GatewayTestResult
    {
        $credentials = $gateway->getCredentials();
        $token = $credentials['api_token'] ?? '';

        if (empty($token)) {
            return GatewayTestResult::failed('BitPay API token is missing.');
        }

        $endpoint = $this->getBaseUrl($gateway) . '/currencies';

        try {
            $response = Http::timeout(8)
                ->withHeaders(['x-accept-version' => '2.0.0'])
                ->get($endpoint);

            if ($response->successful()) {
                return GatewayTestResult::passed('BitPay API responded successfully. Currencies available.', $response->json() ?? []);
            }

            return GatewayTestResult::failed('BitPay test failed with HTTP ' . $response->status());
        } catch (\Throwable $e) {
            return GatewayTestResult::failed('BitPay connection error: ' . $e->getMessage());
        }
    }

    protected function mapBitPayStatus(string $status): string
    {
        return match ($status) {
            'complete', 'confirmed' => CryptoPayment::STATUS_COMPLETED,
            'paid' => CryptoPayment::STATUS_CONFIRMING,
            'new' => CryptoPayment::STATUS_AWAITING_PAYMENT,
            'expired' => CryptoPayment::STATUS_EXPIRED,
            'invalid' => CryptoPayment::STATUS_FAILED,
            default => CryptoPayment::STATUS_PENDING,
        };
    }
}

