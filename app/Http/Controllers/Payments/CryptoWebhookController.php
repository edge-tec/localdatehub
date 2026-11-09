<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Crypto\Services\CryptoWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptoWebhookController extends Controller
{
    public function __construct(
        protected CryptoWebhookProcessor $processor
    ) {}

    /**
     * Handle webhook from NOWPayments.
     */
    public function handleNowPayments(Request $request): JsonResponse
    {
        $res = $this->processor->process('nowpayments', $request);
        return response()->json(['message' => $res['message']], $res['status']);
    }

    /**
     * Handle IPN webhook from CoinPayments.
     */
    public function handleCoinPayments(Request $request): JsonResponse
    {
        $res = $this->processor->process('coinpayments', $request);
        return response()->json(['message' => $res['message']], $res['status']);
    }

    /**
     * Handle webhook from CoinGate.
     */
    public function handleCoinGate(Request $request): JsonResponse
    {
        $res = $this->processor->process('coingate', $request);
        return response()->json(['message' => $res['message']], $res['status']);
    }

    /**
     * Handle IPN webhook from BitPay.
     */
    public function handleBitPay(Request $request): JsonResponse
    {
        $res = $this->processor->process('bitpay', $request);
        return response()->json(['message' => $res['message']], $res['status']);
    }

    /**
     * Handle webhook from Binance Pay.
     */
    public function handleBinancePay(Request $request): JsonResponse
    {
        $res = $this->processor->process('binance_pay', $request);
        return response()->json(['message' => $res['message']], $res['status']);
    }

    /**
     * Handle webhook from Exodus Checkout.
     */
    public function handleExodus(Request $request): JsonResponse
    {
        $res = $this->processor->process('exodus', $request);
        return response()->json(['message' => $res['message']], $res['status']);
    }
}

