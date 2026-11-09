<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Modules\Payments\Crypto\Models\CryptoCurrency;
use App\Modules\Payments\Crypto\Models\CryptoExchangeRate;
use InvalidArgumentException;

class CryptoPricingService
{
    /**
     * Calculate crypto equivalent for a given USD amount.
     * Uses decimal-safe arbitrary precision arithmetic (bcmath).
     */
    public function convertUsdToCrypto(float|string $usdAmount, string $currencySymbol, ?float $markupOverride = null): array
    {
        $symbol = strtoupper($currencySymbol);
        $amountUsd = number_format((float) $usdAmount, 2, '.', '');

        if (bccomp($amountUsd, '0.00', 2) <= 0) {
            throw new InvalidArgumentException("USD amount must be strictly greater than 0, got [{$amountUsd}].");
        }

        $rateRecord = CryptoExchangeRate::where('currency_symbol', $symbol)
            ->where('fiat_currency', 'USD')
            ->first();

        $baseRate = $rateRecord ? (string) $rateRecord->rate : $this->getFallbackRate($symbol);
        $markup = $markupOverride !== null ? (string) $markupOverride : ($rateRecord ? (string) $rateRecord->markup_percentage : '0.00');

        // Effective Rate = baseRate * (1 - markup/100)
        // If markup is positive (e.g. 1%), user pays 1% more USD value -> rate decreases slightly
        $markupFactor = bcsub('1.000000', bcdiv($markup, '100.000000', 6), 6);
        $effectiveRate = bcmul($baseRate, $markupFactor, 8);

        if (bccomp($effectiveRate, '0.00000001', 8) <= 0) {
            $effectiveRate = $baseRate;
        }

        $currency = CryptoCurrency::where('symbol', $symbol)->first();
        $precision = $currency ? $currency->decimal_precision : 8;

        // Required Crypto = Amount USD / Effective Rate
        $cryptoAmount = bcdiv($amountUsd, $effectiveRate, $precision);

        return [
            'fiat_amount_usd' => $amountUsd,
            'currency_symbol' => $symbol,
            'rate' => $effectiveRate,
            'base_rate' => $baseRate,
            'markup_percentage' => $markup,
            'crypto_amount' => $cryptoAmount,
            'precision' => $precision,
        ];
    }

    /**
     * Fallback baseline rates for volatile & pegged assets.
     */
    protected function getFallbackRate(string $symbol): string
    {
        return match ($symbol) {
            'USDT', 'USDC' => '1.00000000',
            'BTC' => '88500.00000000',
            'ETH' => '3120.00000000',
            'BNB' => '645.00000000',
            'TRX' => '0.22500000',
            default => '1.00000000',
        };
    }
}

