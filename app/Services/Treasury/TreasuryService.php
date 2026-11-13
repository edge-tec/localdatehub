<?php

namespace App\Services\Treasury;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\TreasuryWallet;
use InvalidArgumentException;

class TreasuryService
{
    public function resolveAllowlistedDestination(BlockchainNetwork $network, ?CryptoAsset $asset = null): TreasuryWallet
    {
        $query = TreasuryWallet::where('network_id', $network->id)
            ->where('status', 'ACTIVE')
            ->where('is_allowlisted', true);

        if ($asset) {
            $assetSpecific = (clone $query)->where('asset_id', $asset->id)->first();
            if ($assetSpecific) {
                return $assetSpecific;
            }
        }

        $networkDefault = $query->whereNull('asset_id')->first()
            ?? $query->first();

        if (!$networkDefault) {
            throw new InvalidArgumentException("No active allowlisted treasury wallet configured for [{$network->network_code}].");
        }

        return $networkDefault;
    }
}

