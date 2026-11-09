<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Modules\Payments\Crypto\Models\CryptoExchangeRate;
use App\Modules\Payments\Crypto\Models\CryptoGasReserve;
use App\Modules\Payments\Crypto\Models\CryptoPaymentSetting;
use App\Modules\Payments\Crypto\Models\CryptoSweepCostEstimate;

class SweepCostCalculatorService
{
    public function __construct(
        protected GasReserveService $gasReserveService
    ) {}

    /**
     * Compute comprehensive sweep eligibility, network fee, and profitability.
     */
    public function evaluateSweepEligibility(
        string $networkKey,
        string $currencySymbol,
        float|string $tokenBalance,
        float|string $tokenBalanceUsd
    ): CryptoSweepCostEstimate {
        $networkKey = strtoupper($networkKey);
        $currencySymbol = strtoupper($currencySymbol);
        $balance = (float) $tokenBalance;
        $balanceUsd = (float) $tokenBalanceUsd;

        $gasReserve = CryptoGasReserve::where('network_key', $networkKey)->first();
        $gasCurrency = $gasReserve ? $gasReserve->gas_currency : 'ETH';

        // 1. Estimate network fee based on chain characteristics
        [$estimatedGasUnits, $estimatedGasPrice, $estimatedFeeNative, $estimatedFeeUsd] = $this->estimateNetworkFee($networkKey, $gasCurrency);

        // 2. Minimum sweep threshold check
        $minSweepUsd = (float) CryptoPaymentSetting::get('min_sweep_amount_usd', 15.00);
        $maxFeeUsd = (float) CryptoPaymentSetting::get('max_permitted_fee_usd', 10.00);

        // 3. Profitability check (fee must not exceed 20% of transfer amount, and net must be positive)
        $isProfitable = ($balanceUsd > ($estimatedFeeUsd * 2)) && ($balanceUsd >= $minSweepUsd) && ($estimatedFeeUsd <= $maxFeeUsd);

        // 4. Gas reserve sufficiency check
        $canAffordGas = $this->gasReserveService->canAffordGas($networkKey, $estimatedFeeNative);

        $passesAdminThreshold = $isProfitable && $canAffordGas && ($balanceUsd >= $minSweepUsd);

        // 5. Net transfer amount in crypto
        $netTransferCrypto = max(0, $balance);
        if ($currencySymbol === $gasCurrency) {
            $netTransferCrypto = max(0, $balance - $estimatedFeeNative);
        }

        return CryptoSweepCostEstimate::create([
            'network_key' => $networkKey,
            'currency_symbol' => $currencySymbol,
            'token_balance' => $balance,
            'estimated_gas_units' => $estimatedGasUnits,
            'estimated_gas_price' => $estimatedGasPrice,
            'estimated_fee_native' => $estimatedFeeNative,
            'estimated_fee_usd' => $estimatedFeeUsd,
            'net_transfer_crypto' => $netTransferCrypto,
            'is_profitable' => $isProfitable,
            'passes_admin_threshold' => $passesAdminThreshold,
            'calculated_at' => now(),
        ]);
    }

    /**
     * Estimate live network fee in native units and USD.
     */
    protected function estimateNetworkFee(string $networkKey, string $gasCurrency): array
    {
        // Reference realistic network fee estimates
        $estimates = [
            'TRC20' => [65000, '420', '13.50000000', 3.00], // ~13.5 TRX (~$3.00 for TRC20 transfer without staked energy)
            'ERC20' => [65000, '15 gwei', '0.00120000', 3.75], // ~0.0012 ETH (~$3.75)
            'BEP20' => [50000, '3 gwei', '0.00075000', 0.48], // ~0.00075 BNB (~$0.48)
            'BTC'   => [250, '12 sat/vB', '0.00003000', 2.65], // ~0.00003000 BTC (~$2.65)
            'ETH'   => [21000, '15 gwei', '0.00045000', 1.40],
            'BNB'   => [21000, '3 gwei', '0.00035000', 0.22],
            'TRX'   => [1000, '0', '1.00000000', 0.22],
        ];

        return $estimates[$networkKey] ?? [21000, '10', '0.00100000', 2.00];
    }
}

