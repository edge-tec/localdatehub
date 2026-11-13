<?php

namespace App\Services\Treasury;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\GasReserve;
use App\Services\Blockchain\BlockchainMonitor;
use App\Services\Support\BaseUnitMath;

class GasManager
{
    public function __construct(
        protected BlockchainMonitor $monitor
    ) {}

    public function estimateRequiredFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string
    {
        return $this->monitor->adapterFor($network)->estimateSweepFeeBaseUnits($network, $asset);
    }

    public function hasSufficientGasReserve(BlockchainNetwork $network, string $requiredFeeBaseUnits): bool
    {
        $nativeAsset = CryptoAsset::where('network_id', $network->id)
            ->where('asset_type', 'native')
            ->first();

        if (!$nativeAsset) {
            return false;
        }

        $reserve = GasReserve::where('network_id', $network->id)
            ->where('asset_id', $nativeAsset->id)
            ->first();

        if (!$reserve) {
            return false;
        }

        return BaseUnitMath::comp($reserve->available_balance_base_units, $requiredFeeBaseUnits) >= 0;
    }

    public function recordGasConsumption(BlockchainNetwork $network, string $feeBaseUnits): void
    {
        $nativeAsset = CryptoAsset::where('network_id', $network->id)
            ->where('asset_type', 'native')
            ->first();

        if (!$nativeAsset) {
            return;
        }

        $reserve = GasReserve::where('network_id', $network->id)
            ->where('asset_id', $nativeAsset->id)
            ->first();

        if (!$reserve) {
            return;
        }

        $remaining = BaseUnitMath::sub($reserve->available_balance_base_units, $feeBaseUnits);
        if (BaseUnitMath::comp($remaining, '0') < 0) {
            $remaining = '0';
        }

        $status = BaseUnitMath::comp($remaining, $reserve->minimum_reserve_base_units) < 0 ? 'LOW' : 'OPTIMAL';
        $reserve->update([
            'available_balance_base_units' => $remaining,
            'status' => $status,
        ]);
    }
}

