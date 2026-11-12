<?php

namespace App\Modules\Payments\Crypto\Services;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use App\Modules\Payments\Crypto\Gateways\CryptoGatewayManager;
use App\Modules\Payments\Crypto\Models\BoostPlan;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Models\PaymentGateway;
use App\Modules\Payments\Crypto\Support\CryptoCurrencyRegistry;
use Illuminate\Support\Str;

class CryptoPaymentService
{
    public function __construct(
        protected CryptoGatewayManager $gatewayManager,
        protected ListingBoostService $boostService
    ) {}

    /**
     * Create a new cryptocurrency checkout order for boosting a listing.
     */
    public function createBoostCheckout(
        User $user,
        Listing $listing,
        BoostPlan $plan,
        PaymentGateway $gateway,
        string $currency,
        string $network
    ): CryptoPayment {
        $currency = strtoupper($currency);
        $network = strtoupper($network);

        // Generate unique internal order reference ID
        $orderId = 'LDH-CP-' . strtoupper(Str::random(12));

        // Estimate initial crypto units based on fiat price (stablecoins = 1:1, BTC/ETH approximate rates)
        $expectedCrypto = $this->calculateCryptoEstimate($plan->price, $currency);

        $payment = CryptoPayment::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'boost_plan_id' => $plan->id,
            'gateway_id' => $gateway->id,
            'internal_order_id' => $orderId,
            'payment_currency' => $currency,
            'payment_network' => $network,
            'expected_amount' => $expectedCrypto,
            'received_amount' => 0.00000000,
            'fiat_amount' => $plan->price,
            'fiat_currency' => $plan->currency ?: 'USD',
            'status' => CryptoPayment::STATUS_CREATED,
            'expires_at' => now()->addHours(2),
        ]);

        // Request gateway invoice / deposit address
        $adapter = $this->gatewayManager->get($gateway);
        $response = $adapter->createPayment($payment, $gateway);

        if ($response->success) {
            $payment->update([
                'provider_payment_id' => $response->providerPaymentId,
                'provider_invoice_id' => $response->providerInvoiceId,
                'payment_address' => $response->paymentAddress,
                'payment_url' => $response->paymentUrl,
                'qr_code_data' => $response->qrCodeData,
                'expected_amount' => $response->expectedAmount ?: $expectedCrypto,
                'expires_at' => $response->expiresAt ?: now()->addHours(2),
                'status' => CryptoPayment::STATUS_AWAITING_PAYMENT,
                'metadata' => array_merge($payment->metadata ?? [], ['gateway_response' => $response->rawResponse]),
            ]);
        } else {
            $payment->update([
                'status' => CryptoPayment::STATUS_FAILED,
                'metadata' => array_merge($payment->metadata ?? [], ['error' => $response->errorMessage]),
            ]);
        }

        return $payment;
    }

    /**
     * Poll / sync the latest status of an awaiting or confirming payment.
     */
    public function syncPaymentStatus(CryptoPayment $payment): CryptoPayment
    {
        if ($payment->isCompleted() || $payment->status === CryptoPayment::STATUS_EXPIRED) {
            return $payment;
        }

        if ($payment->isExpired()) {
            $payment->update(['status' => CryptoPayment::STATUS_EXPIRED]);
            return $payment;
        }

        $gateway = $payment->gateway;
        if (!$gateway) {
            return $payment;
        }

        $adapter = $this->gatewayManager->get($gateway);
        $statusResponse = $adapter->verifyPayment($payment, $gateway);

        if ($statusResponse->success) {
            $payment->status = $statusResponse->status;
            if ($statusResponse->receivedAmount !== null) {
                $payment->received_amount = (float) $statusResponse->receivedAmount;
            }
            if ($statusResponse->transactionHash !== null) {
                $payment->transaction_hash = $statusResponse->transactionHash;
            }
            if ($statusResponse->confirmations > 0) {
                $payment->confirmations = $statusResponse->confirmations;
            }
            $payment->save();

            if ($payment->status === CryptoPayment::STATUS_COMPLETED) {
                if (!$payment->confirmed_at) {
                    $payment->confirmed_at = now();
                    $payment->save();
                }

                if (!empty($payment->listing_id)) {
                    $this->boostService->activateBoost($payment);
                }
            }
        }

        return $payment;
    }

    /**
     * Public alias to calculate crypto unit estimate for display before provider rates finalize.
     */
    public function estimateCryptoAmount(float|string $fiatUsd, string $currency): string
    {
        return $this->calculateCryptoEstimate($fiatUsd, $currency);
    }

    /**
     * Calculate crypto unit estimate for display before provider rates finalize.
     */
    public function calculateCryptoEstimate(float|string $fiatUsd, string $currency): string
    {
        $fiat = (float) $fiatUsd;
        $c = strtoupper($currency);

        if ($c === 'USDT' || $c === 'USDC') {
            return number_format($fiat, 2, '.', '');
        }

        // Conservative reference baseline exchange rates
        $rates = [
            'BTC' => 88000.0,
            'ETH' => 3100.0,
            'SOL' => 195.0,
            'BNB' => 640.0,
            'LTC' => 95.0,
            'TRX' => 0.22,
            'DOGE' => 0.28,
            'BCH' => 450.0,
            'XRP' => 1.10,
        ];

        $rate = $rates[$c] ?? 1.0;
        $estimate = $fiat / $rate;

        return number_format($estimate, 8, '.', '');
    }
}

