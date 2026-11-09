<?php

namespace Database\Seeders;

use App\Modules\Payments\Crypto\Models\BoostPlan;
use App\Modules\Payments\Crypto\Models\PaymentGateway;
use App\Modules\Payments\Crypto\Support\CryptoCurrencyRegistry;
use Illuminate\Database\Seeder;

class CryptoPaymentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Boost Plans
        $plans = [
            [
                'name' => '24 Hours Boost',
                'slug' => 'boost-24h',
                'description' => 'Immediate spotlight bump to the top of category & search results for 24 hours.',
                'price' => 4.99,
                'currency' => 'USD',
                'duration_days' => 1,
                'featured_position' => 'top_of_search',
                'priority_level' => 1,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => '3 Days Boost',
                'slug' => 'boost-3d',
                'description' => 'Featured placement above standard listings for 3 full days with instant bump.',
                'price' => 9.99,
                'currency' => 'USD',
                'duration_days' => 3,
                'featured_position' => 'top_of_search',
                'priority_level' => 2,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => '7 Days Pro Boost',
                'slug' => 'boost-7d',
                'description' => 'Most popular: 1 full week top placement + highlighted badge + priority ranking.',
                'price' => 19.99,
                'currency' => 'USD',
                'duration_days' => 7,
                'featured_position' => 'homepage_and_category',
                'priority_level' => 3,
                'is_active' => true,
                'display_order' => 3,
            ],
            [
                'name' => '15 Days Maximum Impact',
                'slug' => 'boost-15d',
                'description' => 'Half a month top positioning across city and category searches for serious posters.',
                'price' => 34.99,
                'currency' => 'USD',
                'duration_days' => 15,
                'featured_position' => 'homepage_and_category',
                'priority_level' => 4,
                'is_active' => true,
                'display_order' => 4,
            ],
            [
                'name' => '30 Days VIP Spotlight',
                'slug' => 'boost-30d',
                'description' => 'Full month premium dominance, permanent VIP badge, and maximum local exposure.',
                'price' => 59.99,
                'currency' => 'USD',
                'duration_days' => 30,
                'featured_position' => 'homepage_and_category',
                'priority_level' => 5,
                'is_active' => true,
                'display_order' => 5,
            ],
        ];

        foreach ($plans as $p) {
            BoostPlan::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // 2. Seed Default Crypto Payment Gateways
        $currencies = array_keys(CryptoCurrencyRegistry::getSupportedCurrencies());
        $networks = array_keys(CryptoCurrencyRegistry::getSupportedNetworks());

        $gateways = [
            [
                'name' => 'In-House Crypto Gateway (Direct Trust Wallet)',
                'slug' => 'inhouse',
                'provider' => 'inhouse',
                'is_enabled' => true,
                'environment' => 'live',
                'supported_currencies' => $currencies,
                'supported_networks' => $networks,
                'display_order' => 0,
            ],
            [
                'name' => 'NOWPayments',
                'slug' => 'nowpayments',
                'provider' => 'nowpayments',
                'is_enabled' => false,
                'environment' => 'sandbox',
                'supported_currencies' => $currencies,
                'supported_networks' => $networks,
                'display_order' => 1,
            ],
            [
                'name' => 'CoinPayments',
                'slug' => 'coinpayments',
                'provider' => 'coinpayments',
                'is_enabled' => false,
                'environment' => 'sandbox',
                'supported_currencies' => $currencies,
                'supported_networks' => $networks,
                'display_order' => 2,
            ],
            [
                'name' => 'CoinGate',
                'slug' => 'coingate',
                'provider' => 'coingate',
                'is_enabled' => false,
                'environment' => 'sandbox',
                'supported_currencies' => $currencies,
                'supported_networks' => $networks,
                'display_order' => 3,
            ],
            [
                'name' => 'BitPay',
                'slug' => 'bitpay',
                'provider' => 'bitpay',
                'is_enabled' => false,
                'environment' => 'sandbox',
                'supported_currencies' => ['BTC', 'ETH', 'USDT', 'USDC', 'LTC', 'DOGE', 'BCH', 'XRP'],
                'supported_networks' => ['BTC', 'ERC20', 'LTC', 'DOGE', 'BCH', 'XRP'],
                'display_order' => 4,
            ],
            [
                'name' => 'Binance Pay',
                'slug' => 'binance-pay',
                'provider' => 'binance_pay',
                'is_enabled' => false,
                'environment' => 'sandbox',
                'supported_currencies' => ['USDT', 'BTC', 'ETH', 'BNB', 'SOL', 'XRP', 'DOGE', 'LTC'],
                'supported_networks' => ['BEP20', 'TRC20', 'ERC20', 'POLYGON', 'SOLANA'],
                'display_order' => 5,
            ],
            [
                'name' => 'Exodus Checkout',
                'slug' => 'exodus',
                'provider' => 'exodus',
                'is_enabled' => false,
                'environment' => 'sandbox',
                'supported_currencies' => ['BTC', 'ETH', 'USDT', 'USDC', 'SOL', 'LTC', 'XRP', 'DOGE'],
                'supported_networks' => ['BTC', 'ERC20', 'TRC20', 'SOLANA', 'LTC', 'XRP', 'DOGE'],
                'display_order' => 6,
            ],
        ];

        foreach ($gateways as $g) {
            $model = PaymentGateway::updateOrCreate(['slug' => $g['slug']], $g);
            // If credentials not set, store placeholder test credentials
            if (empty($model->encrypted_credentials)) {
                $model->setCredentials([
                    'api_key' => 'sandbox_test_key_' . $g['slug'],
                    'secret_key' => 'sandbox_test_secret_' . $g['slug'],
                    'public_key' => 'sandbox_test_pubkey_' . $g['slug'],
                    'private_key' => 'sandbox_test_privkey_' . $g['slug'],
                    'api_token' => 'sandbox_test_token_' . $g['slug'],
                ]);
                $model->setWebhookSecret('sandbox_webhook_secret_' . $g['slug']);
                $model->save();
            }
        }
    }
}

