<?php

namespace App\Services\Blockchain;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;

class LitecoinAdapter extends BitcoinAdapter
{
    public function supportsNetworkType(string $networkType): bool
    {
        return strtolower($networkType) === 'utxo';
    }

    public function estimateSweepFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string
    {
        return '10000'; // 10,000 litoshis baseline
    }
}

