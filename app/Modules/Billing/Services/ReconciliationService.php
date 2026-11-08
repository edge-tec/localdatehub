<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\PaymentTransaction;
use Illuminate\Support\Collection;

class ReconciliationService
{
    /**
     * Reconcile a batch of gateway transaction records against local database records.
     *
     * @param string $gateway
     * @param array<int, array{gateway_id: string, reference_id?: string, status: string, amount_cents: int}> $gatewayRecords
     * @return array{total_gateway_records: int, matched: int, updated: int, discrepancies: array}
     */
    public function reconcileGatewayBatch(string $gateway, array $gatewayRecords): array
    {
        $matched = 0;
        $updated = 0;
        $discrepancies = [];

        foreach ($gatewayRecords as $item) {
            $gatewayId = $item['gateway_id'] ?? null;
            $refId = $item['reference_id'] ?? null;
            $gatewayStatus = strtolower($item['status'] ?? 'completed');
            $amountCents = (int) ($item['amount_cents'] ?? 0);

            // Find matching local transaction
            $query = PaymentTransaction::where('gateway', $gateway);
            if ($gatewayId) {
                $query->where(function ($q) use ($gatewayId, $refId) {
                    $q->where('gateway_transaction_id', $gatewayId);
                    if ($refId) {
                        $q->orWhere('reference_id', $refId);
                    }
                });
            } elseif ($refId) {
                $query->where('reference_id', $refId);
            }

            $local = $query->first();

            if (! $local) {
                $discrepancies[] = [
                    'type' => 'missing_locally',
                    'gateway_id' => $gatewayId,
                    'reference_id' => $refId,
                    'amount_cents' => $amountCents,
                    'gateway_status' => $gatewayStatus,
                ];
                continue;
            }

            $matched++;

            // Check for amount mismatch
            if ($local->amount_cents !== $amountCents) {
                $discrepancies[] = [
                    'type' => 'amount_mismatch',
                    'transaction_id' => $local->id,
                    'local_cents' => $local->amount_cents,
                    'gateway_cents' => $amountCents,
                ];
            }

            // Align status if local was pending and gateway is settled/completed
            $needsUpdate = false;
            if ($local->status === 'pending' && in_array($gatewayStatus, ['completed', 'settled', 'success'])) {
                $local->status = 'completed';
                $needsUpdate = true;
            } elseif ($local->status !== 'refunded' && in_array($gatewayStatus, ['refunded', 'reversed'])) {
                $local->status = 'refunded';
                $needsUpdate = true;
            }

            if (! $local->is_reconciled || $needsUpdate) {
                $local->is_reconciled = true;
                $local->reconciled_at = now();
                $local->save();
                $updated++;
            }
        }

        return [
            'total_gateway_records' => count($gatewayRecords),
            'matched' => $matched,
            'updated' => $updated,
            'discrepancies' => $discrepancies,
        ];
    }

    /**
     * Get unreconciled transactions summary.
     */
    public function getUnreconciledTransactions(): Collection
    {
        return PaymentTransaction::where('is_reconciled', false)
            ->where('created_at', '<', now()->subHours(2))
            ->orderByDesc('created_at')
            ->get();
    }
}
