<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\WalletAccount;
use App\Models\Wallet\WalletAddress;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AddressAllocator
{
    public function __construct(
        protected AddressGenerator $generator
    ) {}

    public function allocate(User $user, BlockchainNetwork $network, CryptoAsset $asset): WalletAddress
    {
        if (!$network->is_enabled || !$asset->is_enabled || $asset->network_id !== $network->id) {
            throw new InvalidArgumentException("Asset [{$asset->symbol}] on network [{$network->network_code}] is not enabled.");
        }

        return DB::transaction(function () use ($user, $network, $asset) {
            // Idempotent permanent user-to-address mapping
            $existing = WalletAddress::where('user_id', $user->id)
                ->where('network_id', $network->id)
                ->where('asset_id', $asset->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $account = WalletAccount::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'account_reference' => 'WA-' . str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
                    'account_status' => 'ACTIVE',
                ]
            );

            $maxIndex = WalletAddress::where('network_id', $network->id)
                ->lockForUpdate()
                ->max('derivation_index');

            $nextIndex = $maxIndex === null ? 0 : ((int) $maxIndex + 1);

            $derived = $this->generator->generate($network, $nextIndex);

            return WalletAddress::create([
                'user_id' => $user->id,
                'network_id' => $network->id,
                'asset_id' => $asset->id,
                'wallet_account_id' => $account->id,
                'address' => $derived['address'],
                'derivation_path' => $derived['derivation_path'],
                'derivation_index' => $nextIndex,
                'address_type' => $derived['address_type'],
                'status' => 'ACTIVE',
            ]);
        });
    }
}

