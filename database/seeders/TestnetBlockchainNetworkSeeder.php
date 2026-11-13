<?php

namespace Database\Seeders;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use Illuminate\Database\Seeder;

class TestnetBlockchainNetworkSeeder extends Seeder
{
    public function run(): void
    {
        $networks = [
            [
                'network_code' => 'ETH_SEPOLIA',
                'name' => 'Ethereum Sepolia Testnet',
                'chain_id' => 11155111,
                'network_type' => 'evm',
                'is_testnet' => true,
                'rpc_configuration_reference' => 'https://ethereum-sepolia-rpc.publicnode.com,https://rpc.sepolia.org',
                'confirmation_threshold' => 6,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'ETH', 'name' => 'Sepolia ETH', 'contract_address' => null, 'decimals' => 18, 'asset_type' => 'native', 'minimum_deposit' => '1000000000000000'],
                ],
            ],
            [
                'network_code' => 'BSC_TESTNET',
                'name' => 'BNB Smart Chain Testnet',
                'chain_id' => 97,
                'network_type' => 'evm',
                'is_testnet' => true,
                'rpc_configuration_reference' => 'https://bsc-testnet-rpc.publicnode.com,https://data-seed-prebsc-1-s1.binance.org:8545',
                'confirmation_threshold' => 6,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'BNB', 'name' => 'Testnet BNB', 'contract_address' => null, 'decimals' => 18, 'asset_type' => 'native', 'minimum_deposit' => '1000000000000000'],
                ],
            ],
            [
                'network_code' => 'BTC_TESTNET',
                'name' => 'Bitcoin Testnet3',
                'chain_id' => null,
                'network_type' => 'utxo',
                'is_testnet' => true,
                'rpc_configuration_reference' => 'https://mempool.space/testnet/api,https://blockstream.info/testnet/api',
                'confirmation_threshold' => 3,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'BTC', 'name' => 'Testnet BTC', 'contract_address' => null, 'decimals' => 8, 'asset_type' => 'native', 'minimum_deposit' => '10000'],
                ],
            ],
            [
                'network_code' => 'TRON_SHASTA',
                'name' => 'TRON Shasta Testnet',
                'chain_id' => null,
                'network_type' => 'tron',
                'is_testnet' => true,
                'rpc_configuration_reference' => 'https://api.shasta.trongrid.io',
                'confirmation_threshold' => 10,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'TRX', 'name' => 'Shasta TRX', 'contract_address' => null, 'decimals' => 6, 'asset_type' => 'native', 'minimum_deposit' => '1000000'],
                ],
            ],
            [
                'network_code' => 'SOL_DEVNET',
                'name' => 'Solana Devnet',
                'chain_id' => null,
                'network_type' => 'solana',
                'is_testnet' => true,
                'rpc_configuration_reference' => 'https://api.devnet.solana.com',
                'confirmation_threshold' => 10,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'SOL', 'name' => 'Devnet SOL', 'contract_address' => null, 'decimals' => 9, 'asset_type' => 'native', 'minimum_deposit' => '1000000'],
                ],
            ],
            [
                'network_code' => 'XRP_TESTNET',
                'name' => 'XRP Ledger Testnet',
                'chain_id' => null,
                'network_type' => 'xrp',
                'is_testnet' => true,
                'rpc_configuration_reference' => 'https://s.altnet.rippletest.net:51234',
                'confirmation_threshold' => 1,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'XRP', 'name' => 'Testnet XRP', 'contract_address' => null, 'decimals' => 6, 'asset_type' => 'native', 'minimum_deposit' => '1000000'],
                ],
            ],
        ];

        foreach ($networks as $item) {
            $assets = $item['assets'];
            unset($item['assets']);

            $network = BlockchainNetwork::updateOrCreate(
                ['network_code' => $item['network_code']],
                $item
            );

            foreach ($assets as $assetData) {
                CryptoAsset::updateOrCreate(
                    ['network_id' => $network->id, 'symbol' => $assetData['symbol']],
                    array_merge($assetData, ['is_enabled' => true])
                );
            }
        }
    }
}

