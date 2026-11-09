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
use Illuminate\Support\Str;

class BinancePayAdapter implements CryptoPaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'binance_pay';
    }

    public function getName(): string
    {
        return 'Binance Pay';
    }

    protected function getBaseUrl(): string
    {
        return 'https://bpay.binanceapi.com';
    }

    public function createPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayPaymentResponse
    {
        $credentials = $gateway->getCredentials();
        $apiKey = $credentials['api_key'] ?? '';
        $secretKey = $credentials['secret_key'] ?? '';

        if (empty($apiKey) || empty($secretKey)) {
            return GatewayPaymentResponse::failed('Binance Pay API Key and Secret Key are required.');
        }

        $endpoint = $this->getBaseUrl() . '/binancepay/openapi/v2/order';

        $payload = [
            'env' => [
                'terminalType' => 'WEB',
            ],
            'merchantTradeNo' => $payment->internal_order_id,
            'orderAmount' => (float) $payment->fiat_amount,
            'currency' => strtoupper($payment->payment_currency === 'USDT' ? 'USDT' : 'USD'),
            'goods' => [
                'goodsType' => '02',
                'goodsCategory' => 'Z000',
                'referenceGoodsId' => (string) $payment->boost_plan_id,
                'goodsName' => "Boost Plan: {$payment->boostPlan?->name}",
                'goodsDetail' => "Post boost promotion on LocalDateHub",
            ],
            'returnUrl' => route('account.listings.index', ['boost_success' => 1]),
            'cancelUrl' => route('account.listings.index'),
            'webhookUrl' => url('/webhooks/crypto/binance-pay'),
        ];

        $jsonPayload = json_encode($payload);
        $headers = $this->buildBinanceHeaders($apiKey, $secretKey, $jsonPayload);

        try {
            $response = Http::timeout(15)
                ->withHeaders($headers)
                ->withBody($jsonPayload, 'application/json')
                ->post($endpoint);

            if ($response->successful()) {
                $body = $response->json();
                if (($body['status'] ?? '') === 'SUCCESS' && !empty($body['data'])) {
                    $data = $body['data'];
                    $prepayId = (string) ($data['prepayId'] ?? '');
                    $checkoutUrl = $data['universalUrl'] ?? $data['checkoutUrl'] ?? null;
                    $qrContent = $data['qrContent'] ?? $checkoutUrl;

                    $expiresAt = now()->addHours(1);
                    if (!empty($data['expireTime'])) {
                        $expiresAt = (new \DateTimeImmutable())->setTimestamp((int) ($data['expireTime'] / 1000));
                    }

                    return GatewayPaymentResponse::successful(
                        providerPaymentId: $prepayId,
                        paymentAddress: null,
                        paymentUrl: $checkoutUrl,
                        expectedAmount: (string) $payment->fiat_amount,
                        qrCodeData: $qrContent,
                        providerInvoiceId: $prepayId,
                        expiresAt: $expiresAt,
                        rawResponse: $body
                    );
                }

                return GatewayPaymentResponse::failed('Binance Pay Error: ' . ($body['errorMessage'] ?? $response->body()), $body);
            }

            return GatewayPaymentResponse::failed('Binance Pay HTTP Error: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('Binance Pay createPayment exception', ['error' => $e->getMessage()]);
            return GatewayPaymentResponse::failed('Binance Pay connection error: ' . $e->getMessage());
        }
    }

    public function verifyPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayStatusResponse
    {
        return $this->getPaymentStatus($payment->internal_order_id, $gateway);
    }

    public function getPaymentStatus(string $providerPaymentId, PaymentGateway $gateway): GatewayStatusResponse
    {
        $credentials = $gateway->getCredentials();
        $apiKey = $credentials['api_key'] ?? '';
        $secretKey = $credentials['secret_key'] ?? '';

        if (empty($apiKey) || empty($secretKey)) {
            return GatewayStatusResponse::failed('Binance Pay keys not configured.');
        }

        $endpoint = $this->getBaseUrl() . '/binancepay/openapi/v2/order/query';
        $payload = ['merchantTradeNo' => $providerPaymentId];
        $jsonPayload = json_encode($payload);
        $headers = $this->buildBinanceHeaders($apiKey, $secretKey, $jsonPayload);

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withBody($jsonPayload, 'application/json')
                ->post($endpoint);

            if ($response->successful()) {
                $body = $response->json();
                if (($body['status'] ?? '') === 'SUCCESS' && !empty($body['data'])) {
                    $bStatus = $body['data']['status'] ?? '';
                    $appStatus = $this->mapBinanceStatus($bStatus);

                    return GatewayStatusResponse::successful(
                        status: $appStatus,
                        rawResponse: $body
                    );
                }
            }

            return GatewayStatusResponse::failed('Failed to query Binance order: ' . $response->body());
        } catch (\Throwable $e) {
            return GatewayStatusResponse::failed('Binance query exception: ' . $e->getMessage());
        }
    }

    public function verifyWebhook(Request $request, PaymentGateway $gateway): bool
    {
        $secret = $gateway->getWebhookSecret();
        $credentials = $gateway->getCredentials();
        $secretKey = $secret ?: ($credentials['secret_key'] ?? '');

        if (empty($secretKey)) {
            Log::warning('Binance Pay webhook secret/key not configured.');
            return false;
        }

        $timestamp = $request->header('BinancePay-Timestamp');
        $nonce = $request->header('BinancePay-Nonce');
        $signature = $request->header('BinancePay-Signature');

        if (empty($timestamp) || empty($nonce) || empty($signature)) {
            return false;
        }

        $payload = $request->getContent();
        $message = "{$timestamp}\n{$nonce}\n{$payload}\n";
        $expectedSig = strtoupper(hash_hmac('sha512', $message, $secretKey));

        return hash_equals($expectedSig, strtoupper($signature));
    }

    public function handleWebhook(Request $request, PaymentGateway $gateway): WebhookProcessingResult
    {
        $payload = $request->json()->all();
        $data = $payload['data'] ?? $payload;

        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }

        $merchantTradeNo = $data['merchantTradeNo'] ?? null;
        $prepayId = $data['prepayId'] ?? null;
        $bStatus = $data['status'] ?? '';
        $status = $this->mapBinanceStatus($bStatus);

        return WebhookProcessingResult::successful(
            internalOrderId: $merchantTradeNo,
            providerPaymentId: $prepayId,
            status: $status,
            rawPayload: $payload
        );
    }

    public function getSupportedCurrencies(): array
    {
        return ['USDT', 'BTC', 'ETH', 'BNB', 'BUSD', 'SOL', 'XRP', 'DOGE', 'LTC'];
    }

    public function getSupportedNetworks(string $currency): array
    {
        return ['BEP20', 'TRC20', 'ERC20', 'POLYGON', 'SOLANA'];
    }

    public function refundPayment(CryptoPayment $payment, PaymentGateway $gateway, string $reason): bool
    {
        Log::info("Refund requested for Binance Pay payment {$payment->internal_order_id}: {$reason}");
        return false;
    }

    public function testConnection(PaymentGateway $gateway): GatewayTestResult
    {
        $credentials = $gateway->getCredentials();
        $apiKey = $credentials['api_key'] ?? '';
        $secretKey = $credentials['secret_key'] ?? '';

        if (empty($apiKey) || empty($secretKey)) {
            return GatewayTestResult::failed('Binance Pay API Key and Secret Key are required.');
        }

        $endpoint = $this->getBaseUrl() . '/binancepay/openapi/v2/order/query';
        $payload = ['merchantTradeNo' => 'test-probe-' . time()];
        $jsonPayload = json_encode($payload);
        $headers = $this->buildBinanceHeaders($apiKey, $secretKey, $jsonPayload);

        try {
            $response = Http::timeout(8)
                ->withHeaders($headers)
                ->withBody($jsonPayload, 'application/json')
                ->post($endpoint);

            // An authentic request returns 200 with ORDER_NOT_EXIST status code, proving signature & API keys are valid!
            if ($response->successful()) {
                $body = $response->json();
                if (in_array($body['code'] ?? '', ['000000', '400002'], true) || ($body['status'] ?? '') === 'SUCCESS') {
                    return GatewayTestResult::passed('Binance Pay credentials and signature verified successfully.', $body);
                }

                if (!empty($body['errorMessage'])) {
                    return GatewayTestResult::failed('Binance Pay returned: ' . $body['errorMessage']);
                }
            }

            return GatewayTestResult::failed('Binance Pay test failed with HTTP ' . $response->status());
        } catch (\Throwable $e) {
            return GatewayTestResult::failed('Failed to connect to Binance Pay API: ' . $e->getMessage());
        }
    }

    protected function buildBinanceHeaders(string $apiKey, string $secretKey, string $body): array
    {
        $timestamp = (string) (int) (microtime(true) * 1000);
        $nonce = Str::random(32);
        $message = "{$timestamp}\n{$nonce}\n{$body}\n";
        $signature = strtoupper(hash_hmac('sha512', $message, $secretKey));

        return [
            'Content-Type' => 'application/json',
            'BinancePay-Timestamp' => $timestamp,
            'BinancePay-Nonce' => $nonce,
            'BinancePay-Certificate-SN' => $apiKey,
            'BinancePay-Signature' => $signature,
        ];
    }

    protected function mapBinanceStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'PAID' => CryptoPayment::STATUS_COMPLETED,
            'INITIAL', 'PENDING' => CryptoPayment::STATUS_AWAITING_PAYMENT,
            'EXPIRED' => CryptoPayment::STATUS_EXPIRED,
            'CANCELED' => CryptoPayment::STATUS_CANCELLED,
            'REFUNDED' => CryptoPayment::STATUS_REFUNDED,
            default => CryptoPayment::STATUS_PENDING,
        };
    }
}

