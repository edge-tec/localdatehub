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

class NowPaymentsAdapter implements CryptoPaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'nowpayments';
    }

    public function getName(): string
    {
        return 'NOWPayments';
    }

    protected function getBaseUrl(PaymentGateway $gateway): string
    {
        return $gateway->environment === 'live'
            ? 'https://api.nowpayments.io/v1'
            : 'https://api-sandbox.nowpayments.io/v1';
    }

    public function createPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayPaymentResponse
    {
        $credentials = $gateway->getCredentials();
        $apiKey = $credentials['api_key'] ?? '';

        if (empty($apiKey)) {
            return GatewayPaymentResponse::failed('NOWPayments API key is not configured.');
        }

        $currency = strtolower($payment->payment_currency);
        $network = strtoupper($payment->payment_network);

        // NOWPayments formats token symbols with network suffixes, e.g. usdttrc20, usdterc20, usdtbsc
        $payCurrency = $this->mapCurrencyNetworkToNowPayments($currency, $network);

        $endpoint = $this->getBaseUrl($gateway) . '/payment';

        $payload = [
            'price_amount' => (float) $payment->fiat_amount,
            'price_currency' => strtolower($payment->fiat_currency),
            'pay_currency' => $payCurrency,
            'ipn_callback_url' => url('/webhooks/crypto/nowpayments'),
            'order_id' => $payment->internal_order_id,
            'order_description' => "Boost Plan: {$payment->boostPlan?->name} for Listing #{$payment->listing_id}",
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $paymentId = (string) ($data['payment_id'] ?? '');
                $address = $data['pay_address'] ?? null;
                $expectedAmount = (string) ($data['pay_amount'] ?? $payment->expected_amount);
                $paymentUrl = $data['invoice_url'] ?? null;
                $qrCode = $address ? CryptoCurrencyRegistry::buildQrCodeUri($payment->payment_currency, $payment->payment_network, $address, $expectedAmount) : null;

                $expiresAt = now()->addHours(2);
                if (!empty($data['expiration_estimate_date'])) {
                    try {
                        $expiresAt = new \DateTimeImmutable($data['expiration_estimate_date']);
                    } catch (\Throwable $e) {}
                }

                return GatewayPaymentResponse::successful(
                    providerPaymentId: $paymentId,
                    paymentAddress: $address,
                    paymentUrl: $paymentUrl,
                    expectedAmount: $expectedAmount,
                    qrCodeData: $qrCode,
                    providerInvoiceId: $data['invoice_id'] ?? null,
                    expiresAt: $expiresAt,
                    rawResponse: $data
                );
            }

            Log::error('NOWPayments createPayment error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return GatewayPaymentResponse::failed(
                'NOWPayments error: ' . ($response->json('message') ?? $response->body()),
                $response->json() ?? []
            );
        } catch (\Throwable $e) {
            Log::error('NOWPayments createPayment exception', ['error' => $e->getMessage()]);
            return GatewayPaymentResponse::failed('Connection error: ' . $e->getMessage());
        }
    }

    public function verifyPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayStatusResponse
    {
        if (empty($payment->provider_payment_id)) {
            return GatewayStatusResponse::failed('No provider payment ID present on transaction.');
        }

        return $this->getPaymentStatus($payment->provider_payment_id, $gateway);
    }

    public function getPaymentStatus(string $providerPaymentId, PaymentGateway $gateway): GatewayStatusResponse
    {
        $credentials = $gateway->getCredentials();
        $apiKey = $credentials['api_key'] ?? '';

        if (empty($apiKey)) {
            return GatewayStatusResponse::failed('NOWPayments API key not configured.');
        }

        $endpoint = $this->getBaseUrl($gateway) . "/payment/{$providerPaymentId}";

        try {
            $response = Http::timeout(10)
                ->withHeaders(['x-api-key' => $apiKey])
                ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                $nowStatus = strtolower($data['payment_status'] ?? '');
                $appStatus = $this->mapNowPaymentsStatus($nowStatus);

                return GatewayStatusResponse::successful(
                    status: $appStatus,
                    receivedAmount: isset($data['actually_paid']) ? (string) $data['actually_paid'] : null,
                    transactionHash: $data['payin_hash'] ?? null,
                    confirmations: (int) ($data['confirmations'] ?? 0),
                    rawResponse: $data
                );
            }

            return GatewayStatusResponse::failed('Failed to retrieve status: ' . $response->body());
        } catch (\Throwable $e) {
            return GatewayStatusResponse::failed('Status query exception: ' . $e->getMessage());
        }
    }

    public function verifyWebhook(Request $request, PaymentGateway $gateway): bool
    {
        $secret = $gateway->getWebhookSecret();
        if (empty($secret)) {
            Log::warning('NOWPayments webhook received but IPN secret is not configured.');
            return false;
        }

        $receivedSig = $request->header('x-nowpayments-sig');
        if (empty($receivedSig)) {
            return false;
        }

        $payload = $request->all();
        if (empty($payload)) {
            return false;
        }

        // Sort payload recursively and build JSON representation
        ksort($payload);
        $sortedJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $expectedSig = hash_hmac('sha512', $sortedJson, $secret);

        return hash_equals($expectedSig, $receivedSig);
    }

    public function handleWebhook(Request $request, PaymentGateway $gateway): WebhookProcessingResult
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;
        $paymentId = isset($payload['payment_id']) ? (string) $payload['payment_id'] : null;
        $nowStatus = strtolower($payload['payment_status'] ?? '');
        $status = $this->mapNowPaymentsStatus($nowStatus);

        return WebhookProcessingResult::successful(
            internalOrderId: $orderId,
            providerPaymentId: $paymentId,
            status: $status,
            receivedAmount: isset($payload['actually_paid']) ? (string) $payload['actually_paid'] : null,
            transactionHash: $payload['payin_hash'] ?? null,
            confirmations: (int) ($payload['confirmations'] ?? 0),
            rawPayload: $payload
        );
    }

    public function getSupportedCurrencies(): array
    {
        return ['BTC', 'ETH', 'USDT', 'USDC', 'LTC', 'TRX', 'BNB', 'SOL', 'DOGE', 'BCH', 'XRP'];
    }

    public function getSupportedNetworks(string $currency): array
    {
        return array_keys(CryptoCurrencyRegistry::getNetworksForCurrency($currency));
    }

    public function refundPayment(CryptoPayment $payment, PaymentGateway $gateway, string $reason): bool
    {
        // NOWPayments requires custodial merchant deposit or dashboard ticket for refunds
        Log::info("Refund requested for NOWPayments payment {$payment->internal_order_id}: {$reason}");
        return false;
    }

    public function testConnection(PaymentGateway $gateway): GatewayTestResult
    {
        $credentials = $gateway->getCredentials();
        $apiKey = $credentials['api_key'] ?? '';

        if (empty($apiKey)) {
            return GatewayTestResult::failed('API Key is missing.');
        }

        $endpoint = $this->getBaseUrl($gateway) . '/status';

        try {
            $response = Http::timeout(8)
                ->withHeaders(['x-api-key' => $apiKey])
                ->get($endpoint);

            if ($response->successful()) {
                return GatewayTestResult::passed('NOWPayments API status is operational and API key accepted.', $response->json() ?? []);
            }

            return GatewayTestResult::failed('NOWPayments API returned HTTP ' . $response->status() . ': ' . $response->body());
        } catch (\Throwable $e) {
            return GatewayTestResult::failed('Failed to connect to NOWPayments API: ' . $e->getMessage());
        }
    }

    protected function mapCurrencyNetworkToNowPayments(string $currency, string $network): string
    {
        $c = strtolower($currency);
        $n = strtoupper($network);

        if ($c === 'usdt') {
            if ($n === 'TRC20') return 'usdttrc20';
            if ($n === 'ERC20') return 'usdterc20';
            if ($n === 'BEP20') return 'usdtbsc';
            if ($n === 'POLYGON') return 'usdtmatic';
            if ($n === 'SOLANA') return 'usdtsol';
            return 'usdttrc20';
        }

        if ($c === 'usdc') {
            if ($n === 'ERC20') return 'usdcerc20';
            if ($n === 'POLYGON') return 'usdcmatic';
            if ($n === 'SOLANA') return 'usdcsol';
            return 'usdc';
        }

        if ($c === 'bnb') return 'bnbbsc';

        return $c;
    }

    protected function mapNowPaymentsStatus(string $status): string
    {
        return match ($status) {
            'finished' => CryptoPayment::STATUS_COMPLETED,
            'confirmed', 'sending' => CryptoPayment::STATUS_CONFIRMING,
            'waiting' => CryptoPayment::STATUS_AWAITING_PAYMENT,
            'partially_paid' => CryptoPayment::STATUS_UNDERPAID,
            'expired' => CryptoPayment::STATUS_EXPIRED,
            'failed' => CryptoPayment::STATUS_FAILED,
            'refunded' => CryptoPayment::STATUS_REFUNDED,
            default => CryptoPayment::STATUS_PENDING,
        };
    }
}

