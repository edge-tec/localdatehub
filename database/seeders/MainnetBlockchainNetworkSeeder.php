<?php

namespace Database\Seeders;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use Illuminate\Database\Seeder;

class MainnetBlockchainNetworkSeeder extends Seeder
{
    public function run(): void
    {
        $networks = [
            [
                'network_code' => 'ETH_MAINNET',
                'name' => 'Ethereum Mainnet',
                'chain_id' => 1,
                'network_type' => 'evm',
                'is_testnet' => false,
                'rpc_configuration_reference' => 'https://ethereum-rpc.publicnode.com',
                'explorer_base_url' => 'https://etherscan.io',
                'confirmation_threshold' => 12,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'ETH', 'name' => 'Ethereum', 'contract_address' => null, 'decimals' => 18, 'asset_type' => 'native', 'minimum_deposit' => '1000000000000000'],
                    ['symbol' => 'USDT', 'name' => 'Tether USD (ERC-20)', 'contract_address' => '0xdAC17F958D2ee523a2206206994597C13D831ec7', 'decimals' => 6, 'asset_type' => 'token', 'minimum_deposit' => '5000000'],
                    ['symbol' => 'USDC', 'name' => 'USD Coin (ERC-20)', 'contract_address' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48', 'decimals' => 6, 'asset_type' => 'token', 'minimum_deposit' => '5000000'],
                ],
            ],
            [
                'network_code' => 'BSC_MAINNET',
                'name' => 'BNB Smart Chain',
                'chain_id' => 56,
                'network_type' => 'evm',
                'is_testnet' => false,
                'rpc_configuration_reference' => 'https://bsc-rpc.publicnode.com',
                'explorer_base_url' => 'https://bscscan.com',
                'confirmation_threshold' => 15,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'BNB', 'name' => 'BNB', 'contract_address' => null, 'decimals' => 18, 'asset_type' => 'native', 'minimum_deposit' => '5000000000000000'],
                    ['symbol' => 'USDT', 'name' => 'Tether USD (BEP-20)', 'contract_address' => '0x55d398326f99059fF775485246999027B3197955', 'decimals' => 18, 'asset_type' => 'token', 'minimum_deposit' => '5000000000000000000'],
                    ['symbol' => 'USDC', 'name' => 'USD Coin (BEP-20)', 'contract_address' => '0x8AC76a51cc950d9822D68b83fE1Ad97B32Cd580d', 'decimals' => 18, 'asset_type' => 'token', 'minimum_deposit' => '5000000000000000000'],
                ],
            ],
            [
                'network_code' => 'POLYGON_MAINNET',
                'name' => 'Polygon PoS',
                'chain_id' => 137,
                'network_type' => 'evm',
                'is_testnet' => false,
                'rpc_configuration_reference' => 'https://polygon-bor-rpc.publicnode.com',
                'explorer_base_url' => 'https://polygonscan.com',
                'confirmation_threshold' => 32,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'POL', 'name' => 'Polygon Ecosystem Token', 'contract_address' => null, 'decimals' => 18, 'asset_type' => 'native', 'minimum_deposit' => '1000000000000000000'],
                    ['symbol' => 'USDT', 'name' => 'Tether USD (Polygon)', 'contract_address' => '0xc2132D05D31c914a87C6611C10748AEb04B58e8F', 'decimals' => 6, 'asset_type' => 'token', 'minimum_deposit' => '2000000'],
                    ['symbol' => 'USDC', 'name' => 'USD Coin (Polygon)', 'contract_address' => '0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359', 'decimals' => 6, 'asset_type' => 'token', 'minimum_deposit' => '2000000'],
                ],
            ],
            [
                'network_code' => 'ARBITRUM_MAINNET',
                'name' => 'Arbitrum One',
                'chain_id' => 42161,
                'network_type' => 'evm',
                'is_testnet' => false,
                'rpc_configuration_reference' => 'https://arbitrum-one-rpc.publicnode.com',
                'explorer_base_url' => 'https://arbiscan.io',
                'confirmation_threshold' => 20,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'ETH', 'name' => 'Ethereum (Arbitrum)', 'contract_address' => null, 'decimals' => 18, 'asset_type' => 'native', 'minimum_deposit' => '1000000000000000'],
                    ['symbol' => 'USDT', 'name' => 'Tether USD (Arbitrum)', 'contract_address' => '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9', 'decimals' => 6, 'asset_type' => 'token', 'minimum_deposit' => '2000000'],
                    ['symbol' => 'USDC', 'name' => 'USD Coin (Arbitrum)', 'contract_address' => '0xaf88d065e77c8cC2239327C5EDb3A432268e5831', 'decimals' => 6, 'asset_type' => 'token', 'minimum_deposit' => '2000000'],
                ],
            ],
            [
                'network_code' => 'BASE_MAINNET',
                'name' => 'Base',
                'chain_id' => 8453,
                'network_type' => 'evm',
                'is_testnet' => false,
                'rpc_configuration_reference' => 'https://base-rpc.publicnode.com',
                'explorer_base_url' => 'https://basescan.org',
                'confirmation_threshold' => 20,
                'is_enabled' => false,
                'assets' => [
                    ['symbol' => 'ETH', 'name' => 'Ethereum (Base)', 'contract_address' => null, 'decimals' => 18, 'asset_type' => 'native', 'minimum_deposit' => '1000000000000000'],
                    ['symbol' => 'USDC', 'name' => 'USD Coin (Base)', 'contract_address' => '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913', 'decimals' => 6, 'asset_type' => 'token', 'minimum_deposit' => '2000000'],
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
