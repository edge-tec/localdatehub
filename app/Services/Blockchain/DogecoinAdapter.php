<?php

namespace App\Services\Blockchain;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;

class DogecoinAdapter extends BitcoinAdapter
{
    public function supportsNetworkType(string $networkType): bool
    {
        return strtolower($networkType) === 'utxo';
    }

    public function estimateSweepFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string
    {
        return '100000000'; // 1 DOGE in koinu
    }
}

