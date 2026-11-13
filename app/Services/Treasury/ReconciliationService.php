<?php

namespace App\Services\Treasury;

use App\Models\Wallet\BlockchainTransaction;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\GasReserve;
use App\Models\Wallet\LedgerEntry;
use App\Models\Wallet\SweepTransaction;
use App\Services\Support\BaseUnitMath;

class ReconciliationService
{
    /**
     * Reconcile deposits, ledger entries, sweeps, and gas reserves.
     *
     * @return array{
     *     healthy: bool,
     *     alerts: array<int, array{code: string, message: string, reference_id: int|null}>,
     *     summary: array<string, string|int>
     * }
     */
    public function reconcile(): array
    {
        $alerts = [];

        // 1. Verified deposits not credited
        $verifiedUncredited = Deposit::where('status', Deposit::STATUS_VERIFIED)
            ->whereNull('credited_at')
            ->get();
        foreach ($verifiedUncredited as $dep) {
            $alerts[] = [
                'code' => 'VERIFIED_DEPOSIT_NOT_CREDITED',
                'message' => "Deposit #{$dep->id} is VERIFIED but has no credited_at timestamp.",
                'reference_id' => $dep->id,
            ];
        }

        // 2. Credited deposits missing immutable ledger entry
        $credited = Deposit::whereNotNull('credited_at')->get();
        $totalCreditedBaseUnits = '0';
        foreach ($credited as $dep) {
            $totalCreditedBaseUnits = BaseUnitMath::add($totalCreditedBaseUnits, (string) $dep->credited_amount_base_units);
            $hasLedger = LedgerEntry::where('deposit_id', $dep->id)
                ->where('direction', 'CREDIT')
                ->exists();
            if (!$hasLedger) {
                $alerts[] = [
                    'code' => 'CREDITED_DEPOSIT_MISSING_LEDGER',
                    'message' => "Deposit #{$dep->id} is credited but missing a ledger_entries record.",
                    'reference_id' => $dep->id,
                ];
            }
        }

        // 3. Reorganized transactions
        $reorged = BlockchainTransaction::where('transaction_status', Deposit::STATUS_REORGED)->get();
        foreach ($reorged as $tx) {
            $alerts[] = [
                'code' => 'REORGED_TRANSACTION',
                'message' => "Blockchain transaction {$tx->transaction_hash} was reorganized.",
                'reference_id' => $tx->id,
            ];
        }

        // 4. Low gas reserves
        $lowGas = GasReserve::all()->filter(
            fn (GasReserve $g) => BaseUnitMath::comp($g->available_balance_base_units, $g->minimum_reserve_base_units) < 0
        );
        foreach ($lowGas as $g) {
            $alerts[] = [
                'code' => 'INSUFFICIENT_GAS_RESERVE',
                'message' => "Gas reserve #{$g->id} below minimum threshold.",
                'reference_id' => $g->id,
            ];
        }

        // 5. Sweeps queued or stuck broadcasting
        $sweptTotal = '0';
        $pendingSweepTotal = '0';
        foreach (SweepTransaction::all() as $sw) {
            if ($sw->status === Deposit::STATUS_SWEPT) {
                $sweptTotal = BaseUnitMath::add($sweptTotal, (string) $sw->amount_base_units);
            } else {
                $pendingSweepTotal = BaseUnitMath::add($pendingSweepTotal, (string) $sw->amount_base_units);
            }
        }

        return [
            'healthy' => empty($alerts),
            'alerts' => $alerts,
            'summary' => [
                'credited_deposits_count' => $credited->count(),
                'total_credited_base_units' => $totalCreditedBaseUnits,
                'total_swept_base_units' => $sweptTotal,
                'pending_sweep_base_units' => $pendingSweepTotal,
                'alerts_count' => count($alerts),
            ],
        ];
    }
}

