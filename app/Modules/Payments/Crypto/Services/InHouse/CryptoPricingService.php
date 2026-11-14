<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Modules\Payments\Crypto\Models\CryptoCurrency;
use App\Modules\Payments\Crypto\Models\CryptoExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class CryptoPricingService
{
    /**
     * Calculate crypto equivalent for a given USD amount using real-time market rates.
     * Uses decimal-safe arbitrary precision arithmetic (bcmath) and rounds up to prevent underpayment.
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

        $baseRate = null;
        if ($rateRecord && $rateRecord->is_manual_override && !$rateRecord->isExpired()) {
            $baseRate = (string) $rateRecord->rate;
        } else {
            $liveRate = $this->getLiveUsdRate($symbol);
            if ($liveRate !== null && bccomp($liveRate, '0.00000001', 8) > 0) {
                $baseRate = $liveRate;
            } else {
                $baseRate = $rateRecord ? (string) $rateRecord->rate : $this->getFallbackRate($symbol);
            }
        }

        $markup = $markupOverride !== null ? (string) $markupOverride : ($rateRecord ? (string) $rateRecord->markup_percentage : '0.00');

        // Effective Rate = baseRate * (1 - markup/100)
        // If markup is positive (e.g. 1%), user pays 1% more USD value -> rate decreases slightly
        $markupFactor = bcsub('1.000000', bcdiv($markup, '100.000000', 6), 6);
        $effectiveRate = bcmul($baseRate, $markupFactor, 8);

        if (bccomp($effectiveRate, '0.00000001', 8) <= 0) {
            $effectiveRate = $baseRate;
        }

        $currency = CryptoCurrency::where('symbol', $symbol)->first();
        $rawPrecision = $currency ? (int) $currency->decimal_precision : 8;
        $precision = max(2, min($rawPrecision, 8));

        // Required Crypto = Amount USD / Effective Rate, rounded UP at $precision so USD value is never short
        $cryptoAmount = $this->bcDivRoundUp($amountUsd, $effectiveRate, $precision);

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
     * Fetch real-time USD exchange rate with 60-second caching (Binance -> CoinGecko -> Coinbase).
     */
    public function getLiveUsdRate(string $symbol): ?string
    {
        $symbol = strtoupper($symbol);
        if (in_array($symbol, ['USDT', 'USDC'], true)) {
            return '1.00000000';
        }

        // Keep unit tests deterministic unless an HTTP fake is explicitly active.
        if (app()->runningUnitTests() && !app()->bound('crypto.force_live_rates')) {
            return null;
        }

        $rates = Cache::remember('crypto_live_usd_rates_v1', 60, function () {
            $fetched = $this->fetchFromBinance();
            if (count($fetched) < 5) {
                $fetched = array_merge($this->fetchFromCoinGecko(), $fetched);
            }
            if (count($fetched) < 5) {
                foreach (['BTC', 'ETH', 'BNB', 'LTC', 'TRX'] as $sym) {
                    if (!isset($fetched[$sym])) {
                        $coinbase = $this->fetchSingleFromCoinbase($sym);
                        if ($coinbase !== null) {
                            $fetched[$sym] = $coinbase;
                        }
                    }
                }
            }

            foreach ($fetched as $sym => $price) {
                if (bccomp($price, '0.00000001', 8) > 0) {
                    try {
                        CryptoExchangeRate::updateOrCreate(
                            ['currency_symbol' => $sym, 'fiat_currency' => 'USD'],
                            [
                                'rate' => $price,
                                'rate_source' => 'live_market_api',
                                'is_manual_override' => false,
                                'expires_at' => now()->addMinutes(2),
                            ]
                        );
                    } catch (\Throwable) {
                        // Ignore DB write errors if called before migrations
                    }
                }
            }

            return $fetched;
        });

        return $rates[$symbol] ?? null;
    }

    /**
     * Divide $numerator by $denominator and round UP at $scale decimal places.
     */
    protected function bcDivRoundUp(string $numerator, string $denominator, int $scale): string
    {
        $extended = bcdiv($numerator, $denominator, $scale + 4);
        $truncated = bcdiv($numerator, $denominator, $scale);

        if (bccomp($extended, $truncated, $scale + 4) > 0) {
            $step = '0.' . str_repeat('0', max(0, $scale - 1)) . '1';
            if ($scale === 0) {
                $step = '1';
            }
            return bcadd($truncated, $step, $scale);
        }

        return $truncated;
    }

    /**
     * @return array<string, string>
     */
    protected function fetchFromBinance(): array
    {
        try {
            $response = Http::timeout(4)->get('https://api.binance.com/api/v3/ticker/price', [
                'symbols' => json_encode(['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'LTCUSDT', 'TRXUSDT']),
            ]);

            if (!$response->ok()) {
                return [];
            }

            $map = [];
            foreach ($response->json() ?? [] as $row) {
                if (!empty($row['symbol']) && !empty($row['price']) && is_numeric($row['price'])) {
                    $sym = str_replace('USDT', '', strtoupper((string) $row['symbol']));
                    $map[$sym] = number_format((float) $row['price'], 8, '.', '');
                }
            }

            return $map;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    protected function fetchFromCoinGecko(): array
    {
        try {
            $response = Http::timeout(4)->get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => 'bitcoin,ethereum,binancecoin,litecoin,tron',
                'vs_currencies' => 'usd',
            ]);

            if (!$response->ok()) {
                return [];
            }

            $idToSymbol = [
                'bitcoin' => 'BTC',
                'ethereum' => 'ETH',
                'binancecoin' => 'BNB',
                'litecoin' => 'LTC',
                'tron' => 'TRX',
            ];

            $data = $response->json() ?? [];
            $map = [];
            foreach ($idToSymbol as $id => $sym) {
                if (!empty($data[$id]['usd']) && is_numeric($data[$id]['usd'])) {
                    $map[$sym] = number_format((float) $data[$id]['usd'], 8, '.', '');
                }
            }

            return $map;
        } catch (\Throwable) {
            return [];
        }
    }

    protected function fetchSingleFromCoinbase(string $symbol): ?string
    {
        try {
            $response = Http::timeout(3)->get("https://api.coinbase.com/v2/prices/{$symbol}-USD/spot");
            if (!$response->ok()) {
                return null;
            }
            $amount = $response->json('data.amount');
            return is_numeric($amount) ? number_format((float) $amount, 8, '.', '') : null;
        } catch (\Throwable) {
            return null;
        }
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
            'LTC' => '95.00000000',
            'TRX' => '0.22500000',
            default => '1.00000000',
        };
    }
}

