<?php

namespace Database\Seeders;

use App\Modules\Payments\Crypto\Models\CryptoCurrency;
use App\Modules\Payments\Crypto\Models\CryptoExchangeRate;
use App\Modules\Payments\Crypto\Models\CryptoGasReserve;
use App\Modules\Payments\Crypto\Models\CryptoGasWallet;
use App\Modules\Payments\Crypto\Models\CryptoNetwork;
use App\Modules\Payments\Crypto\Models\CryptoPaymentSetting;
use App\Modules\Payments\Crypto\Models\CryptoTreasuryWallet;
use Illuminate\Database\Seeder;

class InHouseCryptoGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds for In-House Crypto Payment Gateway & Hybrid Treasury.
     */
    public function run(): void
    {
        // 1. Currencies
        $currencies = [
            [
                'name' => 'Tether USD',
                'symbol' => 'USDT',
                'is_active' => true,
                'decimal_precision' => 6,
                'min_deposit_usd' => 0.10,
                'max_deposit_usd' => 25000.00,
                'sort_order' => 1,
            ],
            [
                'name' => 'USD Coin',
                'symbol' => 'USDC',
                'is_active' => true,
                'decimal_precision' => 6,
                'min_deposit_usd' => 0.10,
                'max_deposit_usd' => 25000.00,
                'sort_order' => 2,
            ],
            [
                'name' => 'Bitcoin',
                'symbol' => 'BTC',
                'is_active' => true,
                'decimal_precision' => 8,
                'min_deposit_usd' => 0.10,
                'max_deposit_usd' => 50000.00,
                'sort_order' => 3,
            ],
            [
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'is_active' => true,
                'decimal_precision' => 18,
                'min_deposit_usd' => 0.10,
                'max_deposit_usd' => 50000.00,
                'sort_order' => 4,
            ],
            [
                'name' => 'BNB',
                'symbol' => 'BNB',
                'is_active' => true,
                'decimal_precision' => 18,
                'min_deposit_usd' => 0.10,
                'max_deposit_usd' => 20000.00,
                'sort_order' => 5,
            ],
            [
                'name' => 'TRON',
                'symbol' => 'TRX',
                'is_active' => true,
                'decimal_precision' => 6,
                'min_deposit_usd' => 0.10,
                'max_deposit_usd' => 10000.00,
                'sort_order' => 6,
            ],
            [
                'name' => 'Litecoin',
                'symbol' => 'LTC',
                'is_active' => true,
                'decimal_precision' => 8,
                'min_deposit_usd' => 0.10,
                'max_deposit_usd' => 25000.00,
                'sort_order' => 7,
            ],
        ];

        $currencyModels = [];
        foreach ($currencies as $curr) {
            $currencyModels[$curr['symbol']] = CryptoCurrency::updateOrCreate(
                ['symbol' => $curr['symbol']],
                $curr
            );
        }

        // 2. Networks
        $networks = [
            // USDT TRC20
            [
                'currency_id' => $currencyModels['USDT']->id,
                'network_key' => 'TRC20',
                'chain_name' => 'TRON Network',
                'chain_id' => 728126428,
                'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
                'gas_currency' => 'TRX',
                'required_confirmations' => 1,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://api.trongrid.io',
                'rpc_endpoint_fallback' => 'https://api.tronstack.io',
                'explorer_tx_url' => 'https://tronscan.org/#/transaction/',
                'explorer_address_url' => 'https://tronscan.org/#/address/',
                'network_fee_estimate_usd' => 1.50,
                'payment_timeout_minutes' => 30,
            ],
            // USDT ERC20
            [
                'currency_id' => $currencyModels['USDT']->id,
                'network_key' => 'ERC20',
                'chain_name' => 'Ethereum Mainnet',
                'chain_id' => 1,
                'contract_address' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
                'gas_currency' => 'ETH',
                'required_confirmations' => 12,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://eth.llamarpc.com',
                'rpc_endpoint_fallback' => 'https://rpc.ankr.com/eth',
                'explorer_tx_url' => 'https://etherscan.io/tx/',
                'explorer_address_url' => 'https://etherscan.io/address/',
                'network_fee_estimate_usd' => 4.00,
                'payment_timeout_minutes' => 45,
            ],
            // USDT BEP20
            [
                'currency_id' => $currencyModels['USDT']->id,
                'network_key' => 'BEP20',
                'chain_name' => 'BNB Smart Chain',
                'chain_id' => 56,
                'contract_address' => '0x55d398326f99059fF775485246999027B3197955',
                'gas_currency' => 'BNB',
                'required_confirmations' => 15,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://bsc-dataseed.binance.org',
                'rpc_endpoint_fallback' => 'https://rpc.ankr.com/bsc',
                'explorer_tx_url' => 'https://bscscan.com/tx/',
                'explorer_address_url' => 'https://bscscan.com/address/',
                'network_fee_estimate_usd' => 0.40,
                'payment_timeout_minutes' => 30,
            ],
            // USDC ERC20
            [
                'currency_id' => $currencyModels['USDC']->id,
                'network_key' => 'ERC20',
                'chain_name' => 'Ethereum Mainnet',
                'chain_id' => 1,
                'contract_address' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48',
                'gas_currency' => 'ETH',
                'required_confirmations' => 12,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://eth.llamarpc.com',
                'rpc_endpoint_fallback' => 'https://rpc.ankr.com/eth',
                'explorer_tx_url' => 'https://etherscan.io/tx/',
                'explorer_address_url' => 'https://etherscan.io/address/',
                'network_fee_estimate_usd' => 4.00,
                'payment_timeout_minutes' => 45,
            ],
            // BTC Native
            [
                'currency_id' => $currencyModels['BTC']->id,
                'network_key' => 'BTC',
                'chain_name' => 'Bitcoin Core',
                'chain_id' => null,
                'contract_address' => null,
                'gas_currency' => 'BTC',
                'required_confirmations' => 1,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://blockstream.info/api',
                'rpc_endpoint_fallback' => 'https://mempool.space/api',
                'explorer_tx_url' => 'https://mempool.space/tx/',
                'explorer_address_url' => 'https://mempool.space/address/',
                'network_fee_estimate_usd' => 2.50,
                'payment_timeout_minutes' => 60,
            ],
            // ETH Native
            [
                'currency_id' => $currencyModels['ETH']->id,
                'network_key' => 'ETH',
                'chain_name' => 'Ethereum Mainnet',
                'chain_id' => 1,
                'contract_address' => null,
                'gas_currency' => 'ETH',
                'required_confirmations' => 12,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://eth.llamarpc.com',
                'rpc_endpoint_fallback' => 'https://rpc.ankr.com/eth',
                'explorer_tx_url' => 'https://etherscan.io/tx/',
                'explorer_address_url' => 'https://etherscan.io/address/',
                'network_fee_estimate_usd' => 2.50,
                'payment_timeout_minutes' => 45,
            ],
            // BNB Native
            [
                'currency_id' => $currencyModels['BNB']->id,
                'network_key' => 'BNB',
                'chain_name' => 'BNB Smart Chain',
                'chain_id' => 56,
                'contract_address' => null,
                'gas_currency' => 'BNB',
                'required_confirmations' => 15,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://bsc-dataseed.binance.org',
                'rpc_endpoint_fallback' => 'https://rpc.ankr.com/bsc',
                'explorer_tx_url' => 'https://bscscan.com/tx/',
                'explorer_address_url' => 'https://bscscan.com/address/',
                'network_fee_estimate_usd' => 0.25,
                'payment_timeout_minutes' => 30,
            ],
            // TRX Native
            [
                'currency_id' => $currencyModels['TRX']->id,
                'network_key' => 'TRX',
                'chain_name' => 'TRON Network',
                'chain_id' => 728126428,
                'contract_address' => null,
                'gas_currency' => 'TRX',
                'required_confirmations' => 1,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://api.trongrid.io',
                'rpc_endpoint_fallback' => 'https://api.tronstack.io',
                'explorer_tx_url' => 'https://tronscan.org/#/transaction/',
                'explorer_address_url' => 'https://tronscan.org/#/address/',
                'network_fee_estimate_usd' => 0.30,
                'payment_timeout_minutes' => 30,
            ],
            // LTC Native
            [
                'currency_id' => $currencyModels['LTC']->id,
                'network_key' => 'LTC',
                'chain_name' => 'Litecoin Mainnet',
                'chain_id' => null,
                'contract_address' => null,
                'gas_currency' => 'LTC',
                'required_confirmations' => 2,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://api.blockcypher.com/v1/ltc/main',
                'rpc_endpoint_fallback' => 'https://litecoinspace.org/api',
                'explorer_tx_url' => 'https://litecoinspace.org/tx/',
                'explorer_address_url' => 'https://litecoinspace.org/address/',
                'network_fee_estimate_usd' => 0.05,
                'payment_timeout_minutes' => 45,
            ],
            // USDT POLYGON
            [
                'currency_id' => $currencyModels['USDT']->id,
                'network_key' => 'POLYGON',
                'chain_name' => 'Polygon PoS',
                'chain_id' => 137,
                'contract_address' => '0xc2132D05D31c914a87C6611C10748AEb04B58e8F',
                'gas_currency' => 'MATIC',
                'required_confirmations' => 15,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://polygon-rpc.com',
                'rpc_endpoint_fallback' => 'https://rpc.ankr.com/polygon',
                'explorer_tx_url' => 'https://polygonscan.com/tx/',
                'explorer_address_url' => 'https://polygonscan.com/address/',
                'network_fee_estimate_usd' => 0.05,
                'payment_timeout_minutes' => 30,
            ],
            // USDC POLYGON
            [
                'currency_id' => $currencyModels['USDC']->id,
                'network_key' => 'POLYGON',
                'chain_name' => 'Polygon PoS',
                'chain_id' => 137,
                'contract_address' => '0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359',
                'gas_currency' => 'MATIC',
                'required_confirmations' => 15,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://polygon-rpc.com',
                'rpc_endpoint_fallback' => 'https://rpc.ankr.com/polygon',
                'explorer_tx_url' => 'https://polygonscan.com/tx/',
                'explorer_address_url' => 'https://polygonscan.com/address/',
                'network_fee_estimate_usd' => 0.05,
                'payment_timeout_minutes' => 30,
            ],
            // USDC BEP20
            [
                'currency_id' => $currencyModels['USDC']->id,
                'network_key' => 'BEP20',
                'chain_name' => 'BNB Smart Chain',
                'chain_id' => 56,
                'contract_address' => '0x8AC76a51cc950d9822D68b83fE1Ad97B32Cd580d',
                'gas_currency' => 'BNB',
                'required_confirmations' => 15,
                'is_testnet' => false,
                'is_active' => true,
                'rpc_endpoint_primary' => 'https://bsc-dataseed.binance.org',
                'rpc_endpoint_fallback' => 'https://rpc.ankr.com/bsc',
                'explorer_tx_url' => 'https://bscscan.com/tx/',
                'explorer_address_url' => 'https://bscscan.com/address/',
                'network_fee_estimate_usd' => 0.40,
                'payment_timeout_minutes' => 30,
            ],
        ];

        foreach ($networks as $net) {
            CryptoNetwork::updateOrCreate(
                [
                    'currency_id' => $net['currency_id'],
                    'network_key' => $net['network_key'],
                    'is_testnet' => $net['is_testnet'],
                ],
                $net
            );
        }

        // 3. System Gateway Settings
        $settings = [
            'treasury_emergency_pause' => false,
            'auto_sweep_threshold_usd' => 100.00,
            'large_transfer_approval_threshold_usd' => 500.00,
            'min_sweep_amount_usd' => 15.00,
            'max_auto_sweep_amount_usd' => 500.00,
            'daily_sweep_limit_usd' => 10000.00,
            'auto_sweep_enabled' => true,
            'manual_sweep_enabled' => true,
            'underpayment_threshold_percentage' => 2.0, // allow 2% tolerance if configured
            'late_payment_policy' => 'manual_review', // credit_balance, manual_review, reject
            'hd_derivation_account_index' => 0,
        ];

        foreach ($settings as $key => $val) {
            CryptoPaymentSetting::set($key, $val, "Gateway setting for {$key}");
        }

        // 4. Default Exchange Rates (USD baseline)
        $rates = [
            'USDT' => ['rate' => 1.00000000, 'source' => 'pegged_stablecoin'],
            'USDC' => ['rate' => 1.00000000, 'source' => 'pegged_stablecoin'],
            'BTC'  => ['rate' => 88500.00000000, 'source' => 'coingecko_api'],
            'ETH'  => ['rate' => 3120.00000000, 'source' => 'coingecko_api'],
            'BNB'  => ['rate' => 645.00000000, 'source' => 'binance_api'],
            'TRX'  => ['rate' => 0.22500000, 'source' => 'coingecko_api'],
        ];

        foreach ($rates as $sym => $info) {
            CryptoExchangeRate::updateOrCreate(
                ['currency_symbol' => $sym, 'fiat_currency' => 'USD'],
                [
                    'rate' => $info['rate'],
                    'rate_source' => $info['source'],
                    'markup_percentage' => 0.00,
                    'is_manual_override' => false,
                    'expires_at' => now()->addHours(6),
                ]
            );
        }

        // 5. Trust Wallet Treasury Configuration (Placeholders ready for admin's real Trust Wallet public receiving addresses)
        $treasuries = [
            [
                'wallet_name' => 'Temporary Collection Treasury (Trust Wallet)',
                'tier' => CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET,
                'network_key' => 'TRC20',
                'public_receiving_address' => 'TW_TEMP_TREASURY_TRON_RECEIVING_ADDR_PLACEHOLDER',
                'current_balance' => 0,
                'available_balance' => 0,
                'is_active' => true,
            ],
            [
                'wallet_name' => 'Temporary Collection Treasury (Trust Wallet EVM)',
                'tier' => CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET,
                'network_key' => 'ERC20',
                'public_receiving_address' => '0x000000000000000000000000000000000000dEaD',
                'current_balance' => 0,
                'available_balance' => 0,
                'is_active' => true,
            ],
            [
                'wallet_name' => 'Main Cold Treasury (Primary Trust Wallet TRON)',
                'tier' => CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET,
                'network_key' => 'TRC20',
                'public_receiving_address' => 'TW_MAIN_TREASURY_TRON_COLD_ADDR_PLACEHOLDER',
                'current_balance' => 0,
                'available_balance' => 0,
                'is_active' => true,
            ],
            [
                'wallet_name' => 'Main Cold Treasury (Primary Trust Wallet EVM)',
                'tier' => CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET,
                'network_key' => 'ERC20',
                'public_receiving_address' => '0x000000000000000000000000000000000000bEEF',
                'current_balance' => 0,
                'available_balance' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($treasuries as $t) {
            CryptoTreasuryWallet::updateOrCreate(
                [
                    'tier' => $t['tier'],
                    'network_key' => $t['network_key'],
                    'public_receiving_address' => $t['public_receiving_address'],
                ],
                $t
            );
        }

        // 6. Phase 14.5: Gas Reserves & Gas Wallets
        $gasReserves = [
            [
                'network_key' => 'TRC20',
                'gas_currency' => 'TRX',
                'current_balance' => 500.0,
                'min_reserve_threshold' => 100.0,
                'emergency_reserve_threshold' => 30.0,
                'target_reserve' => 1000.0,
                'replenishment_threshold' => 150.0,
                'daily_spending_limit' => 2500.0,
                'spent_today' => 0,
                'is_alert_active' => false,
            ],
            [
                'network_key' => 'ERC20',
                'gas_currency' => 'ETH',
                'current_balance' => 0.50,
                'min_reserve_threshold' => 0.10,
                'emergency_reserve_threshold' => 0.03,
                'target_reserve' => 1.50,
                'replenishment_threshold' => 0.15,
                'daily_spending_limit' => 2.00,
                'spent_today' => 0,
                'is_alert_active' => false,
            ],
            [
                'network_key' => 'BEP20',
                'gas_currency' => 'BNB',
                'current_balance' => 2.00,
                'min_reserve_threshold' => 0.50,
                'emergency_reserve_threshold' => 0.15,
                'target_reserve' => 5.00,
                'replenishment_threshold' => 0.75,
                'daily_spending_limit' => 10.00,
                'spent_today' => 0,
                'is_alert_active' => false,
            ],
            [
                'network_key' => 'BTC',
                'gas_currency' => 'BTC',
                'current_balance' => 0.05,
                'min_reserve_threshold' => 0.01,
                'emergency_reserve_threshold' => 0.003,
                'target_reserve' => 0.10,
                'replenishment_threshold' => 0.015,
                'daily_spending_limit' => 0.20,
                'spent_today' => 0,
                'is_alert_active' => false,
            ],
        ];

        foreach ($gasReserves as $gr) {
            CryptoGasReserve::updateOrCreate(
                ['network_key' => $gr['network_key']],
                $gr
            );
        }

        // 7. Payment Gateway Registration
        \App\Modules\Payments\Crypto\Models\PaymentGateway::updateOrCreate(
            ['slug' => 'inhouse'],
            [
                'name' => 'In-House Crypto Gateway (Direct Trust Wallet)',
                'provider' => 'inhouse',
                'is_enabled' => true,
                'environment' => 'live',
                'supported_currencies' => ['USDT', 'USDC', 'BTC', 'ETH', 'BNB', 'TRX', 'SOL', 'LTC'],
                'supported_networks' => ['TRC20', 'ERC20', 'BEP20', 'BTC', 'SOLANA'],
                'display_order' => 0,
                'last_tested_at' => now(),
                'last_test_status' => 'passed',
                'last_test_message' => 'Direct blockchain nodes and Hybrid Trust Wallet operational.',
            ]
        );
    }
}

