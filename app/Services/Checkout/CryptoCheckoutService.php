<?php

namespace App\Services\Checkout;

use App\Models\User;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\CryptoCheckoutOrder;
use App\Models\Wallet\Deposit;
use App\Services\Support\BaseUnitMath;
use App\Services\Wallet\HDWalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CryptoCheckoutService
{
    public function __construct(
        protected HDWalletService $hdWallet
    ) {}

    public function createOrder(
        User $user,
        BlockchainNetwork $network,
        CryptoAsset $asset,
        string $expectedAmountBaseUnits,
        string $title = 'LocalDateHub Crypto Payment',
        int $ttlMinutes = 30
    ): CryptoCheckoutOrder {
        $amount = BaseUnitMath::normalize($expectedAmountBaseUnits);
        if (!BaseUnitMath::isPositive($amount)) {
            throw new InvalidArgumentException('Checkout expected amount must be positive.');
        }

        $address = $this->hdWallet->getOrAssignDepositAddress($user, $network, $asset);

        return CryptoCheckoutOrder::create([
            'order_reference' => 'LDH-' . strtoupper(Str::random(12)),
            'user_id' => $user->id,
            'network_id' => $network->id,
            'asset_id' => $asset->id,
            'wallet_address_id' => $address->id,
            'title' => $title,
            'expected_amount_base_units' => $amount,
            'received_amount_base_units' => '0',
            'status' => CryptoCheckoutOrder::STATUS_PENDING,
            'expires_at' => now()->addMinutes(max(5, $ttlMinutes)),
        ]);
    }

    /**
     * Settle an open checkout order strictly from a backend-verified deposit.
     */
    public function reconcileDepositToOrder(Deposit $deposit): ?CryptoCheckoutOrder
    {
        if ($deposit->credited_at === null) {
            return null;
        }

        return DB::transaction(function () use ($deposit) {
            if (CryptoCheckoutOrder::where('deposit_id', $deposit->id)->exists()) {
                return CryptoCheckoutOrder::where('deposit_id', $deposit->id)->first();
            }

            $order = CryptoCheckoutOrder::where('user_id', $deposit->user_id)
                ->where('wallet_address_id', $deposit->wallet_address_id)
                ->where('asset_id', $deposit->asset_id)
                ->whereIn('status', [CryptoCheckoutOrder::STATUS_PENDING, CryptoCheckoutOrder::STATUS_CONFIRMING])
                ->lockForUpdate()
                ->oldest('id')
                ->first();

            if (!$order) {
                return null;
            }

            $received = BaseUnitMath::normalize((string) $deposit->amount_base_units);
            $expected = BaseUnitMath::normalize((string) $order->expected_amount_base_units);
            $meta = $order->metadata ?? [];

            if ($order->expires_at && $order->expires_at->isPast()) {
                $meta['late_payment'] = true;
                $order->update([
                    'deposit_id' => $deposit->id,
                    'received_amount_base_units' => $received,
                    'status' => CryptoCheckoutOrder::STATUS_EXPIRED,
                    'metadata' => $meta,
                ]);

                return $order->fresh();
            }

            $cmp = BaseUnitMath::comp($received, $expected);
            if ($cmp < 0) {
                $meta['shortfall_base_units'] = BaseUnitMath::sub($expected, $received);
                $order->update([
                    'deposit_id' => $deposit->id,
                    'received_amount_base_units' => $received,
                    'status' => CryptoCheckoutOrder::STATUS_UNDERPAID,
                    'metadata' => $meta,
                ]);
            } elseif ($cmp > 0) {
                $meta['overpaid_base_units'] = BaseUnitMath::sub($received, $expected);
                $order->update([
                    'deposit_id' => $deposit->id,
                    'received_amount_base_units' => $received,
                    'status' => CryptoCheckoutOrder::STATUS_OVERPAID,
                    'paid_at' => now(),
                    'metadata' => $meta,
                ]);
            } else {
                $order->update([
                    'deposit_id' => $deposit->id,
                    'received_amount_base_units' => $received,
                    'status' => CryptoCheckoutOrder::STATUS_PAID,
                    'paid_at' => now(),
                ]);
            }

            return $order->fresh();
        });
    }
}
