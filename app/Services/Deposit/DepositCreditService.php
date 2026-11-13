<?php

namespace App\Services\Deposit;

use App\Events\WalletDepositUpdated;
use App\Jobs\Wallet\DispatchAutomaticSweep;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\LedgerEntry;
use App\Models\Wallet\WalletAuditLog;
use App\Services\Checkout\CryptoCheckoutService;
use App\Services\Support\BaseUnitMath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DepositCreditService
{
    /**
     * Atomically credit a verified deposit to the user's ledger and dispatch sweep separately.
     */
    public function creditVerifiedDeposit(Deposit $deposit, bool $dispatchSweep = true): LedgerEntry
    {
        $entry = DB::transaction(function () use ($deposit) {
            $locked = Deposit::where('id', $deposit->id)->lockForUpdate()->firstOrFail();

            $idempotencyKey = "DEPOSIT_CREDIT_{$locked->id}_{$locked->blockchain_transaction_id}";
            $existing = LedgerEntry::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            if ($locked->status !== Deposit::STATUS_VERIFIED) {
                throw new InvalidArgumentException(
                    "Cannot credit deposit #{$locked->id} in state [{$locked->status}]; VERIFIED required."
                );
            }

            $amount = BaseUnitMath::normalize($locked->amount_base_units);
            if (!BaseUnitMath::isPositive($amount)) {
                throw new InvalidArgumentException('Deposit credit amount must be positive.');
            }

            $ledger = LedgerEntry::create([
                'user_id' => $locked->user_id,
                'deposit_id' => $locked->id,
                'transaction_type' => 'DEPOSIT_CREDIT',
                'direction' => 'CREDIT',
                'amount_base_units' => $amount,
                'asset_id' => $locked->asset_id,
                'reference' => 'LED-' . date('Ymd') . '-' . strtoupper(Str::random(10)),
                'idempotency_key' => $idempotencyKey,
                'created_at' => now(),
            ]);

            $locked->update([
                'credited_amount_base_units' => $amount,
                'status' => Deposit::STATUS_CREDITED,
                'credited_at' => now(),
            ]);

            WalletAuditLog::create([
                'actor_id' => $locked->user_id,
                'action' => 'DEPOSIT_CREDITED',
                'resource_type' => 'deposit',
                'resource_id' => (string) $locked->id,
                'metadata' => [
                    'amount_base_units' => $amount,
                    'ledger_reference' => $ledger->reference,
                ],
                'created_at' => now(),
            ]);

            return $ledger;
        });

        $freshDeposit = $deposit->fresh();
        app(CryptoCheckoutService::class)->reconcileDepositToOrder($freshDeposit);
        event(new WalletDepositUpdated($freshDeposit));

        if ($dispatchSweep) {
            DispatchAutomaticSweep::dispatch($deposit->id)
                ->onQueue((string) config('custody.queues.sweeping', 'sweeping'));
        }

        return $entry;
    }
}

