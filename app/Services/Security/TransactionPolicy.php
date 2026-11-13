<?php

namespace App\Services\Security;

use App\Models\Wallet\BlockchainNetwork;
use App\Services\Support\BaseUnitMath;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class TransactionPolicy
{
    public const EMERGENCY_PAUSE_CACHE_KEY = 'custody:emergency_outbound_pause';

    public function isOutboundPaused(): bool
    {
        return (bool) Cache::get(self::EMERGENCY_PAUSE_CACHE_KEY, false);
    }

    public function pauseOutbound(): void
    {
        Cache::forever(self::EMERGENCY_PAUSE_CACHE_KEY, true);
    }

    public function resumeOutbound(): void
    {
        Cache::forever(self::EMERGENCY_PAUSE_CACHE_KEY, false);
    }

    public function assertOutboundAllowed(
        BlockchainNetwork $network,
        string $amountBaseUnits
    ): void {
        if ($this->isOutboundPaused()) {
            throw new RuntimeException('Outbound treasury sweeps are currently paused by emergency security policy.');
        }

        if ((bool) config('custody.testnet_only', true) && !$network->is_testnet) {
            throw new RuntimeException(
                "Mainnet outbound sweep blocked for [{$network->network_code}] while CUSTODY_TESTNET_ONLY is enabled."
            );
        }

        if (!BaseUnitMath::isPositive($amountBaseUnits)) {
            throw new RuntimeException('Outbound sweep amount must be strictly positive.');
        }

        $limits = config('custody.policies.max_single_sweep_base_units', []);
        $max = (string) ($limits[strtoupper($network->network_code)] ?? $limits['DEFAULT'] ?? '0');

        if (BaseUnitMath::isPositive($max) && BaseUnitMath::comp($amountBaseUnits, $max) > 0) {
            throw new RuntimeException(
                "Sweep amount [{$amountBaseUnits}] exceeds single-transaction policy limit [{$max}] for [{$network->network_code}]."
            );
        }
    }
}

