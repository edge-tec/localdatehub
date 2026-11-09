<?php

namespace App\Modules\Payments\Crypto\Support;

class CryptoCurrencyRegistry
{
    /**
     * Map of supported cryptocurrencies and their metadata.
     */
    public static function all(): array
    {
        return self::getSupportedCurrencies();
    }

    /**
     * Map of supported cryptocurrencies and their metadata.
     */
    public static function getSupportedCurrencies(): array
    {
        return [
            'BTC' => [
                'name' => 'Bitcoin',
                'symbol' => 'BTC',
                'icon' => 'bitcoin',
                'default_network' => 'BTC',
                'networks' => ['BTC'],
                'decimals' => 8,
                'min_confirmations' => 1,
            ],
            'ETH' => [
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'icon' => 'ethereum',
                'default_network' => 'ERC20',
                'networks' => ['ERC20'],
                'decimals' => 18,
                'min_confirmations' => 2,
            ],
            'USDT' => [
                'name' => 'Tether USD',
                'symbol' => 'USDT',
                'icon' => 'tether',
                'default_network' => 'TRC20',
                'networks' => ['TRC20', 'ERC20', 'BEP20', 'POLYGON', 'SOLANA'],
                'decimals' => 6,
                'min_confirmations' => 1,
            ],
            'USDC' => [
                'name' => 'USD Coin',
                'symbol' => 'USDC',
                'icon' => 'usdc',
                'default_network' => 'ERC20',
                'networks' => ['ERC20', 'POLYGON', 'SOLANA', 'BEP20'],
                'decimals' => 6,
                'min_confirmations' => 1,
            ],
            'LTC' => [
                'name' => 'Litecoin',
                'symbol' => 'LTC',
                'icon' => 'litecoin',
                'default_network' => 'LTC',
                'networks' => ['LTC'],
                'decimals' => 8,
                'min_confirmations' => 1,
            ],
            'TRX' => [
                'name' => 'Tron',
                'symbol' => 'TRX',
                'icon' => 'tron',
                'default_network' => 'TRON',
                'networks' => ['TRON'],
                'decimals' => 6,
                'min_confirmations' => 1,
            ],
            'BNB' => [
                'name' => 'BNB',
                'symbol' => 'BNB',
                'icon' => 'bnb',
                'default_network' => 'BEP20',
                'networks' => ['BEP20'],
                'decimals' => 18,
                'min_confirmations' => 3,
            ],
            'SOL' => [
                'name' => 'Solana',
                'symbol' => 'SOL',
                'icon' => 'solana',
                'default_network' => 'SOLANA',
                'networks' => ['SOLANA'],
                'decimals' => 9,
                'min_confirmations' => 1,
            ],
            'DOGE' => [
                'name' => 'Dogecoin',
                'symbol' => 'DOGE',
                'icon' => 'dogecoin',
                'default_network' => 'DOGE',
                'networks' => ['DOGE'],
                'decimals' => 8,
                'min_confirmations' => 1,
            ],
            'BCH' => [
                'name' => 'Bitcoin Cash',
                'symbol' => 'BCH',
                'icon' => 'bitcoincash',
                'default_network' => 'BCH',
                'networks' => ['BCH'],
                'decimals' => 8,
                'min_confirmations' => 1,
            ],
            'XRP' => [
                'name' => 'XRP',
                'symbol' => 'XRP',
                'icon' => 'xrp',
                'default_network' => 'XRP',
                'networks' => ['XRP'],
                'decimals' => 6,
                'min_confirmations' => 1,
            ],
        ];
    }

    /**
     * Map of blockchain networks with human-readable labels.
     */
    public static function getSupportedNetworks(): array
    {
        return [
            'BTC' => [
                'code' => 'BTC',
                'name' => 'Bitcoin Mainnet',
                'protocol' => 'Native',
                'warning' => 'Only send BTC via Bitcoin Mainnet.',
            ],
            'ERC20' => [
                'code' => 'ERC20',
                'name' => 'Ethereum (ERC-20)',
                'protocol' => 'Ethereum',
                'warning' => 'Only send ERC-20 tokens via Ethereum Mainnet. High gas fees may apply.',
            ],
            'TRC20' => [
                'code' => 'TRC20',
                'name' => 'Tron (TRC-20)',
                'protocol' => 'TRON',
                'warning' => 'Low fees and fast confirmation on the TRON network.',
            ],
            'BEP20' => [
                'code' => 'BEP20',
                'name' => 'BNB Smart Chain (BEP-20)',
                'protocol' => 'BSC',
                'warning' => 'Only send BEP-20 tokens via BNB Smart Chain.',
            ],
            'POLYGON' => [
                'code' => 'POLYGON',
                'name' => 'Polygon Network',
                'protocol' => 'Polygon PoS',
                'warning' => 'Fast and ultra-low fee on Polygon.',
            ],
            'SOLANA' => [
                'code' => 'SOLANA',
                'name' => 'Solana Network (SPL)',
                'protocol' => 'Solana',
                'warning' => 'Near-instant confirmation on the Solana blockchain.',
            ],
            'LTC' => [
                'code' => 'LTC',
                'name' => 'Litecoin Mainnet',
                'protocol' => 'Native',
                'warning' => 'Only send LTC via Litecoin network.',
            ],
            'TRON' => [
                'code' => 'TRON',
                'name' => 'TRON Network',
                'protocol' => 'Native',
                'warning' => 'Only send TRX via TRON network.',
            ],
            'DOGE' => [
                'code' => 'DOGE',
                'name' => 'Dogecoin Mainnet',
                'protocol' => 'Native',
                'warning' => 'Only send DOGE via Dogecoin network.',
            ],
            'BCH' => [
                'code' => 'BCH',
                'name' => 'Bitcoin Cash Mainnet',
                'protocol' => 'Native',
                'warning' => 'Only send BCH via Bitcoin Cash network.',
            ],
            'XRP' => [
                'code' => 'XRP',
                'name' => 'XRP Ledger',
                'protocol' => 'Native',
                'warning' => 'Include Destination Tag if provided.',
            ],
        ];
    }

    /**
     * Get networks available for a specific currency.
     */
    public static function getNetworksForCurrency(string $currency): array
    {
        $currency = strtoupper($currency);
        $currencies = self::getSupportedCurrencies();

        if (!isset($currencies[$currency])) {
            return [];
        }

        $allNetworks = self::getSupportedNetworks();
        $allowed = $currencies[$currency]['networks'];

        $result = [];
        foreach ($allowed as $net) {
            if (isset($allNetworks[$net])) {
                $result[$net] = $allNetworks[$net];
            }
        }

        return $result;
    }

    /**
     * Build QR code data string.
     * By default returns pure, clean blockchain address for 100% universal compatibility
     * across all mobile exchange scanners (Binance, Bybit, KuCoin, OKX, Trust Wallet withdrawal).
     */
    public static function buildQrCodeUri(string $currency, string $network, string $address, string $amount, bool $includeUriScheme = false): string
    {
        if (!$includeUriScheme) {
            return $address;
        }

        $currency = strtoupper($currency);
        $network = strtoupper($network);

        if ($currency === 'BTC' || $network === 'BTC') {
            return "bitcoin:{$address}?amount={$amount}";
        }
        if ($currency === 'ETH' || $network === 'ERC20') {
            return "ethereum:{$address}?amount={$amount}";
        }
        if ($network === 'TRC20' || $currency === 'TRX') {
            return "tron:{$address}?amount={$amount}&token=TRC20";
        }
        if ($network === 'SOLANA' || $currency === 'SOL') {
            return "solana:{$address}?amount={$amount}";
        }
        if ($currency === 'LTC') {
            return "litecoin:{$address}?amount={$amount}";
        }
        if ($currency === 'DOGE') {
            return "dogecoin:{$address}?amount={$amount}";
        }

        return $address;
    }

    /**
     * Alias for buildQrCodeUri for wallet compatibility.
     */
    public static function formatQrUri(string $currency, string $address, string $amount, string $network = '', bool $includeUriScheme = false): string
    {
        return self::buildQrCodeUri($currency, $network ?: $currency, $address, $amount, $includeUriScheme);
    }

    /**
     * Generate mathematically valid blockchain address adhering to strict network checksum rules.
     */
    public static function generateMockAddress(string $currency, string $network = ''): string
    {
        $currency = strtoupper($currency);
        $network = strtoupper($network ?: $currency);

        return app(\App\Modules\Payments\Crypto\Services\InHouse\HdWalletService::class)
            ->generateDeterministicAddress($network, rand(100, 99999));
    }
}

