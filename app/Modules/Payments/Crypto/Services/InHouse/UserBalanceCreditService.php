<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Modules\Monetization\Models\WalletTransaction;
use App\Modules\Monetization\Services\UserWalletService;
use App\Modules\Payments\Crypto\Models\CryptoInternalLedger;
use App\Modules\Payments\Crypto\Models\CryptoPaymentInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserBalanceCreditService
{
    public function __construct(
        protected UserWalletService $walletService
    ) {}

    /**
     * Atomically credit user balance for a confirmed crypto payment invoice.
     * Enforces strict idempotency, row-locking, and immutable financial ledger logging.
     */
    public function creditUserForInvoice(CryptoPaymentInvoice $invoice): CryptoInternalLedger
    {
        return DB::transaction(function () use ($invoice) {
            // Re-fetch invoice with row lock
            $lockedInvoice = CryptoPaymentInvoice::where('id', $invoice->id)->lockForUpdate()->firstOrFail();

            // Prevent duplicate credit
            if ($lockedInvoice->credited_at !== null) {
                return CryptoInternalLedger::where('invoice_id', $lockedInvoice->id)->firstOrFail();
            }

            $user = $lockedInvoice->user;
            $fiatUsd = (float) $lockedInvoice->fiat_amount_usd;
            $idempotencyKey = "CRYPTO_CREDIT_{$lockedInvoice->invoice_number}";

            // 1. Credit User Wallet in existing system
            // In Localdatehub, user_wallets balance stores credits (1 USD = 1 credit or package credits)
            $creditAmount = $lockedInvoice->creditPackage 
                ? (int) ($lockedInvoice->creditPackage->credits + $lockedInvoice->creditPackage->bonus_credits) 
                : (int) round($fiatUsd);

            $this->walletService->credit(
                user: $user,
                amount: max(1, $creditAmount),
                type: WalletTransaction::TYPE_CREDIT_PURCHASE,
                description: "Crypto Deposit Verified ({$lockedInvoice->invoice_number} - {$lockedInvoice->currency_symbol})",
                actorId: $user->id,
                paymentId: null,
                idempotencyKey: $idempotencyKey
            );

            // 2. Fetch current wallet balance for ledger
            $wallet = $this->walletService->getOrCreateWallet($user);
            $balanceAfter = (float) $wallet->balance;
            $balanceBefore = max(0, $balanceAfter - $creditAmount);

            // 3. Record Immutable Crypto Internal Ledger Entry
            $ledger = CryptoInternalLedger::create([
                'user_id' => $user->id,
                'invoice_id' => $lockedInvoice->id,
                'entry_type' => CryptoInternalLedger::TYPE_DEPOSIT_CREDIT,
                'amount_usd' => $lockedInvoice->fiat_amount_usd,
                'balance_before_usd' => $balanceBefore,
                'balance_after_usd' => $balanceAfter,
                'reference_code' => 'LEDGER-' . date('Ymd') . '-' . strtoupper(Str::random(10)),
                'idempotency_key' => $idempotencyKey,
                'audit_notes' => "Automatic blockchain verification credit for invoice {$lockedInvoice->invoice_number}. TX: {$lockedInvoice->tx_hash}",
            ]);

            // 4. Mark invoice as credited
            $lockedInvoice->update([
                'credited_at' => now(),
            ]);

            return $ledger;
        });
    }
}

