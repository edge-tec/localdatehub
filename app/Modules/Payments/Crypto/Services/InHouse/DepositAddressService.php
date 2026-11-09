<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Models\User;
use App\Modules\Payments\Crypto\Models\CryptoCurrency;
use App\Modules\Payments\Crypto\Models\CryptoDepositAddress;
use App\Modules\Payments\Crypto\Models\CryptoNetwork;
use Illuminate\Support\Facades\DB;

class DepositAddressService
{
    public function __construct(
        protected HdWalletService $hdWalletService
    ) {}

    /**
     * Allocate or generate a unique deposit address for a user on a given currency and network.
     */
    public function allocateAddress(User $user, CryptoCurrency $currency, CryptoNetwork $network): CryptoDepositAddress
    {
        return DB::transaction(function () use ($user, $currency, $network) {
            // Find existing unassigned address or create new derived index
            $lastIndex = (int) CryptoDepositAddress::where('network_id', $network->id)->max('derivation_index');
            $newIndex = $lastIndex + 1;

            $publicAddress = $this->hdWalletService->derivePublicAddress($network, $newIndex);

            return CryptoDepositAddress::create([
                'user_id' => $user->id,
                'currency_id' => $currency->id,
                'network_id' => $network->id,
                'public_address' => $publicAddress,
                'derivation_index' => $newIndex,
                'derivation_path' => "m/44'/{$network->chain_id}'/0'/0/{$newIndex}",
                'is_assigned' => true,
            ]);
        });
    }
}

