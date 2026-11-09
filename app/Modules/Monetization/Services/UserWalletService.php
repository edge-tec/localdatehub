<?php

namespace App\Modules\Monetization\Services;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use App\Modules\Monetization\Models\UserWallet;
use App\Modules\Monetization\Models\WalletTransaction;
use App\Support\Exceptions\InsufficientCreditsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserWalletService
{
    /**
     * Retrieve or create a user's wallet with row locking.
     */
    public function getOrCreateWallet(User $user, bool $lock = false): UserWallet
    {
        $query = UserWallet::where('user_id', $user->id);
        if ($lock) {
            $query->lockForUpdate();
        }

        $wallet = $query->first();
        if (!$wallet) {
            $wallet = UserWallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'total_purchased' => 0,
                'total_spent' => 0,
                'currency' => 'CREDITS',
            ]);

            if ($lock) {
                return UserWallet::where('id', $wallet->id)->lockForUpdate()->first();
            }
        }

        return $wallet;
    }

    /**
     * Safely credit a user's wallet.
     *
     * @param User $user
     * @param int $amount Positive integer of credits
     * @param string $type
     * @param string $description
     * @param int|null $actorId
     * @param int|null $paymentId
     * @param string|null $idempotencyKey
     * @return WalletTransaction
     */
    public function credit(
        User $user,
        int $amount,
        string $type,
        string $description,
        ?int $actorId = null,
        ?int $paymentId = null,
        ?string $idempotencyKey = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Credit amount must be strictly positive, got [{$amount}].");
        }

        return DB::transaction(function () use ($user, $amount, $type, $description, $actorId, $paymentId, $idempotencyKey) {
            // Check idempotency
            if ($idempotencyKey) {
                $existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            $wallet = $this->getOrCreateWallet($user, true);

            $before = $wallet->balance;
            $after = $before + $amount;

            $wallet->balance = $after;
            if (in_array($type, [WalletTransaction::TYPE_CREDIT_PURCHASE, WalletTransaction::TYPE_BONUS_CREDIT], true)) {
                $wallet->total_purchased += $amount;
            }
            $wallet->save();

            $tx = WalletTransaction::create([
                'user_id' => $user->id,
                'reference' => 'WTX-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'crypto_payment_id' => $paymentId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'actor_id' => $actorId ?: $user->id,
            ]);

            return $tx;
        });
    }

    /**
     * Safely debit a user's wallet.
     *
     * @param User $user
     * @param int $amount Positive integer of credits to deduct
     * @param string $type
     * @param string $description
     * @param int|null $listingId
     * @param int|null $actorId
     * @param string|null $idempotencyKey
     * @return WalletTransaction
     *
     * @throws InsufficientCreditsException
     */
    public function debit(
        User $user,
        int $amount,
        string $type,
        string $description,
        ?int $listingId = null,
        ?int $actorId = null,
        ?string $idempotencyKey = null
    ): WalletTransaction {
        if ($amount < 0) {
            throw new \InvalidArgumentException("Debit amount must be positive, got [{$amount}].");
        }

        // If zero cost, simply record transaction or return null
        if ($amount === 0) {
            $wallet = $this->getOrCreateWallet($user, false);
            return WalletTransaction::create([
                'user_id' => $user->id,
                'reference' => 'WTX-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
                'type' => $type,
                'amount' => 0,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance,
                'listing_id' => $listingId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description . ' (Free Quota / Zero Cost)',
                'actor_id' => $actorId ?: $user->id,
            ]);
        }

        return DB::transaction(function () use ($user, $amount, $type, $description, $listingId, $actorId, $idempotencyKey) {
            if ($idempotencyKey) {
                $existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            $wallet = $this->getOrCreateWallet($user, true);

            if ($wallet->balance < $amount) {
                throw new InsufficientCreditsException(
                    "Insufficient credits. Required: {$amount} credits, Available: {$wallet->balance} credits. Please purchase credits before publishing."
                );
            }

            $before = $wallet->balance;
            $after = $before - $amount;

            $wallet->balance = $after;
            $wallet->total_spent += $amount;
            $wallet->save();

            $tx = WalletTransaction::create([
                'user_id' => $user->id,
                'reference' => 'WTX-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
                'type' => $type,
                'amount' => -$amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'listing_id' => $listingId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'actor_id' => $actorId ?: $user->id,
            ]);

            return $tx;
        });
    }

    /**
     * Safely refund/reverse a previous transaction.
     */
    public function refund(WalletTransaction $originalTx, string $reason, ?int $actorId = null): WalletTransaction
    {
        return DB::transaction(function () use ($originalTx, $reason, $actorId) {
            $user = $originalTx->user;
            $refundAmount = abs($originalTx->amount);

            $wallet = $this->getOrCreateWallet($user, true);
            $before = $wallet->balance;
            $after = $before + $refundAmount;

            $wallet->balance = $after;
            if ($wallet->total_spent >= $refundAmount) {
                $wallet->total_spent -= $refundAmount;
            }
            $wallet->save();

            return WalletTransaction::create([
                'user_id' => $user->id,
                'reference' => 'WTX-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
                'type' => WalletTransaction::TYPE_REFUND,
                'amount' => $refundAmount,
                'balance_before' => $before,
                'balance_after' => $after,
                'listing_id' => $originalTx->listing_id,
                'idempotency_key' => 'refund_' . $originalTx->reference,
                'description' => "Refund for {$originalTx->reference}: {$reason}",
                'actor_id' => $actorId,
            ]);
        });
    }
}

