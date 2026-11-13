<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Modules\Payments\Crypto\Models\CryptoGasAlert;
use App\Modules\Payments\Crypto\Models\CryptoGasReserve;
use App\Modules\Payments\Crypto\Models\CryptoGasTransaction;
use App\Modules\Payments\Crypto\Models\CryptoPaymentSetting;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GasReserveService
{
    /**
     * Check if a network's gas reserve can safely cover an estimated transaction fee
     * without breaching the protected emergency reserve threshold.
     */
    protected function resolveReserve(string $networkKey): ?CryptoGasReserve
    {
        $networkKey = strtoupper($networkKey);
        $reserve = CryptoGasReserve::where('network_key', $networkKey)->first();

        if (!$reserve) {
            $fallbackMap = [
                'BNB' => 'BEP20',
                'ETH' => 'ERC20',
                'TRX' => 'TRC20',
                'POLYGON' => 'ERC20',
                'MATIC' => 'ERC20',
            ];
            if (isset($fallbackMap[$networkKey])) {
                $reserve = CryptoGasReserve::where('network_key', $fallbackMap[$networkKey])->first();
            }
        }

        return $reserve;
    }

    public function canAffordGas(string $networkKey, float|string $estimatedFeeNative): bool
    {
        $networkKey = strtoupper($networkKey);
        $reserve = $this->resolveReserve($networkKey);

        if (!$reserve) {
            return false;
        }

        $fee = (float) $estimatedFeeNative;
        $balance = (float) $reserve->current_balance;
        $emergencyLimit = (float) $reserve->emergency_reserve_threshold;
        $dailyLimit = (float) $reserve->daily_spending_limit;
        $spentToday = (float) $reserve->spent_today;

        // Daily spending limit check
        if (($spentToday + $fee) > $dailyLimit) {
            $this->createAlert($networkKey, $reserve->gas_currency, CryptoGasAlert::LEVEL_WARNING, 
                "Daily gas spending limit exceeded: [{$spentToday} + {$fee} > {$dailyLimit} {$reserve->gas_currency}].");
            return false;
        }

        // Emergency reserve protection
        if (($balance - $fee) < $emergencyLimit) {
            $this->createAlert($networkKey, $reserve->gas_currency, CryptoGasAlert::LEVEL_CRITICAL, 
                "Gas reserve would breach emergency threshold: available [{$balance}], fee [{$fee}], emergency limit [{$emergencyLimit}].");
            return false;
        }

        return true;
    }

    /**
     * Deduct spent gas fee from network reserve atomically.
     */
    public function recordGasSpending(
        string $networkKey,
        float|string $feeNative,
        float|string $feeUsd,
        ?int $sweepTransactionId = null,
        ?string $txHash = null
    ): CryptoGasTransaction {
        return DB::transaction(function () use ($networkKey, $feeNative, $feeUsd, $sweepTransactionId, $txHash) {
            $networkKey = strtoupper($networkKey);
            $resolved = $this->resolveReserve($networkKey);
            $reserve = $resolved
                ? CryptoGasReserve::where('id', $resolved->id)->lockForUpdate()->firstOrFail()
                : CryptoGasReserve::where('network_key', $networkKey)->lockForUpdate()->firstOrFail();

            $fee = (float) $feeNative;
            $reserve->current_balance = max(0, (float) $reserve->current_balance - $fee);
            $reserve->spent_today = (float) $reserve->spent_today + $fee;

            if ($reserve->current_balance <= (float) $reserve->replenishment_threshold) {
                $reserve->is_alert_active = true;
                $this->createAlert($networkKey, $reserve->gas_currency, CryptoGasAlert::LEVEL_WARNING,
                    "Gas reserve is below replenishment threshold: [{$reserve->current_balance} {$reserve->gas_currency}].");
            }

            $reserve->save();

            return CryptoGasTransaction::create([
                'sweep_transaction_id' => $sweepTransactionId,
                'network_key' => $networkKey,
                'gas_currency' => $reserve->gas_currency,
                'fee_amount' => $feeNative,
                'fee_usd' => $feeUsd,
                'tx_hash' => $txHash,
                'status' => 'CONFIRMED',
            ]);
        });
    }

    /**
     * Create gas alert record if not already active.
     */
    public function createAlert(string $networkKey, string $currency, string $level, string $message): CryptoGasAlert
    {
        return CryptoGasAlert::create([
            'network_key' => strtoupper($networkKey),
            'gas_currency' => strtoupper($currency),
            'alert_level' => $level,
            'message' => $message,
            'is_resolved' => false,
        ]);
    }
}

