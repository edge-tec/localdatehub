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

class CoinPaymentsAdapter implements CryptoPaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'coinpayments';
    }

    public function getName(): string
    {
        return 'CoinPayments';
    }

    protected function getApiUrl(): string
    {
        return 'https://www.coinpayments.net/api.php';
    }

    public function createPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayPaymentResponse
    {
        $credentials = $gateway->getCredentials();
        $publicKey = $credentials['public_key'] ?? '';
        $privateKey = $credentials['private_key'] ?? '';

        if (empty($publicKey) || empty($privateKey)) {
            return GatewayPaymentResponse::failed('CoinPayments Public and Private keys are required.');
        }

        $currency = strtoupper($payment->payment_currency);
        $network = strtoupper($payment->payment_network);
        $coinSymbol = $this->mapCurrencyToCoinPayments($currency, $network);

        $params = [
            'cmd' => 'create_transaction',
            'amount' => (float) $payment->fiat_amount,
            'currency1' => 'USD',
            'currency2' => $coinSymbol,
            'buyer_email' => $payment->user?->email ?? 'billing@localdatehub.com',
            'buyer_name' => $payment->user?->username ?? 'LocalDateHub User',
            'item_name' => "Post Boost: {$payment->boostPlan?->name}",
            'custom' => $payment->internal_order_id,
            'ipn_url' => url('/webhooks/crypto/coinpayments'),
            'format' => 'json',
            'version' => 1,
            'key' => $publicKey,
        ];

        $postData = http_build_query($params, '', '&');
        $hmac = hash_hmac('sha512', $postData, $privateKey);

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->withHeaders(['HMAC' => $hmac])
                ->post($this->getApiUrl(), $params);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['error'] ?? '') !== 'ok') {
                    return GatewayPaymentResponse::failed('CoinPayments API error: ' . ($data['error'] ?? 'Unknown error'), $data);
                }

                $res = $data['result'] ?? [];
                $txnId = $res['txn_id'] ?? '';
                $address = $res['address'] ?? null;
                $amount = (string) ($res['amount'] ?? $payment->expected_amount);
                $checkoutUrl = $res['status_url'] ?? $res['checkout_url'] ?? null;
                $qrCode = $res['qrcode_url'] ?? ($address ? CryptoCurrencyRegistry::buildQrCodeUri($payment->payment_currency, $payment->payment_network, $address, $amount) : null);

                $timeoutSecs = (int) ($res['timeout'] ?? 7200);
                $expiresAt = now()->addSeconds($timeoutSecs);

                return GatewayPaymentResponse::successful(
                    providerPaymentId: $txnId,
                    paymentAddress: $address,
                    paymentUrl: $checkoutUrl,
                    expectedAmount: $amount,
                    qrCodeData: $qrCode,
                    providerInvoiceId: $txnId,
                    expiresAt: $expiresAt,
                    rawResponse: $data
                );
            }

            return GatewayPaymentResponse::failed('CoinPayments HTTP Error: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('CoinPayments createPayment exception', ['error' => $e->getMessage()]);
            return GatewayPaymentResponse::failed('CoinPayments exception: ' . $e->getMessage());
        }
    }

    public function verifyPayment(CryptoPayment $payment, PaymentGateway $gateway): GatewayStatusResponse
    {
        if (empty($payment->provider_payment_id)) {
            return GatewayStatusResponse::failed('Missing CoinPayments transaction ID.');
        }

        return $this->getPaymentStatus($payment->provider_payment_id, $gateway);
    }

    public function getPaymentStatus(string $providerPaymentId, PaymentGateway $gateway): GatewayStatusResponse
    {
        $credentials = $gateway->getCredentials();
        $publicKey = $credentials['public_key'] ?? '';
        $privateKey = $credentials['private_key'] ?? '';

        if (empty($publicKey) || empty($privateKey)) {
            return GatewayStatusResponse::failed('CoinPayments keys not configured.');
        }

        $params = [
            'cmd' => 'get_tx_info',
            'txid' => $providerPaymentId,
            'full' => 1,
            'format' => 'json',
            'version' => 1,
            'key' => $publicKey,
        ];

        $postData = http_build_query($params, '', '&');
        $hmac = hash_hmac('sha512', $postData, $privateKey);

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->withHeaders(['HMAC' => $hmac])
                ->post($this->getApiUrl(), $params);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['error'] ?? '') !== 'ok') {
                    return GatewayStatusResponse::failed('CoinPayments query error: ' . ($data['error'] ?? ''));
                }

                $res = $data['result'] ?? [];
                $statusCode = (int) ($res['status'] ?? 0);
                $status = $this->mapCoinPaymentsStatusCode($statusCode);

                return GatewayStatusResponse::successful(
                    status: $status,
                    receivedAmount: isset($res['receivedf']) ? (string) $res['receivedf'] : null,
                    confirmations: (int) ($res['recv_confirms'] ?? 0),
                    rawResponse: $data
                );
            }

            return GatewayStatusResponse::failed('Status query failed: ' . $response->body());
        } catch (\Throwable $e) {
            return GatewayStatusResponse::failed('Status error: ' . $e->getMessage());
        }
    }

    public function verifyWebhook(Request $request, PaymentGateway $gateway): bool
    {
        $secret = $gateway->getWebhookSecret();
        if (empty($secret)) {
            Log::warning('CoinPayments IPN secret is not configured.');
            return false;
        }

        $hmac = $request->header('HMAC') ?: $request->server('HTTP_HMAC');
        if (empty($hmac)) {
            return false;
        }

        $rawPost = $request->getContent();
        if (empty($rawPost)) {
            return false;
        }

        $calculatedHmac = hash_hmac('sha512', $rawPost, $secret);
        return hash_equals($calculatedHmac, $hmac);
    }

    public function handleWebhook(Request $request, PaymentGateway $gateway): WebhookProcessingResult
    {
        $payload = $request->all();
        $orderId = $payload['custom'] ?? null;
        $txnId = $payload['txn_id'] ?? null;
        $statusCode = (int) ($payload['status'] ?? 0);
        $status = $this->mapCoinPaymentsStatusCode($statusCode);

        return WebhookProcessingResult::successful(
            internalOrderId: $orderId,
            providerPaymentId: $txnId,
            status: $status,
            receivedAmount: isset($payload['amount2']) ? (string) $payload['amount2'] : null,
            transactionHash: $payload['send_tx'] ?? null,
            confirmations: 1,
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
        Log::info("Refund requested for CoinPayments payment {$payment->internal_order_id}: {$reason}");
        return false;
    }

    public function testConnection(PaymentGateway $gateway): GatewayTestResult
    {
        $credentials = $gateway->getCredentials();
        $publicKey = $credentials['public_key'] ?? '';
        $privateKey = $credentials['private_key'] ?? '';

        if (empty($publicKey) || empty($privateKey)) {
            return GatewayTestResult::failed('CoinPayments Public and Private keys are required.');
        }

        $params = [
            'cmd' => 'get_basic_info',
            'format' => 'json',
            'version' => 1,
            'key' => $publicKey,
        ];

        $postData = http_build_query($params, '', '&');
        $hmac = hash_hmac('sha512', $postData, $privateKey);

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->withHeaders(['HMAC' => $hmac])
                ->post($this->getApiUrl(), $params);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['error'] ?? '') === 'ok') {
                    return GatewayTestResult::passed('CoinPayments API connection verified. Merchant: ' . ($data['result']['merchant_id'] ?? 'OK'), $data['result'] ?? []);
                }
                return GatewayTestResult::failed('CoinPayments Error: ' . ($data['error'] ?? 'Invalid response'));
            }

            return GatewayTestResult::failed('HTTP ' . $response->status() . ': ' . $response->body());
        } catch (\Throwable $e) {
            return GatewayTestResult::failed('Connection exception: ' . $e->getMessage());
        }
    }

    protected function mapCurrencyToCoinPayments(string $currency, string $network): string
    {
        $c = strtoupper($currency);
        $n = strtoupper($network);

        if ($c === 'USDT') {
            if ($n === 'TRC20') return 'USDT.TRC20';
            if ($n === 'ERC20') return 'USDT.ERC20';
            if ($n === 'BEP20') return 'USDT.BEP20';
            return 'USDT.TRC20';
        }

        if ($c === 'USDC') {
            if ($n === 'ERC20') return 'USDC.ERC20';
            if ($n === 'BEP20') return 'USDC.BEP20';
            return 'USDC';
        }

        return $c;
    }

    protected function mapCoinPaymentsStatusCode(int $status): string
    {
        if ($status >= 100 || $status === 2) {
            return CryptoPayment::STATUS_COMPLETED;
        }
        if ($status === 1) {
            return CryptoPayment::STATUS_CONFIRMING;
        }
        if ($status === 0) {
            return CryptoPayment::STATUS_AWAITING_PAYMENT;
        }
        if ($status < 0) {
            return CryptoPayment::STATUS_CANCELLED;
        }

        return CryptoPayment::STATUS_PENDING;
    }
}

