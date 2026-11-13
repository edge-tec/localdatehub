<?php

namespace App\Services\Security;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\TreasuryWallet;
use InvalidArgumentException;

class TreasuryAddressValidator
{
    public function validateAllowlistedTreasury(
        BlockchainNetwork $network,
        string $destinationAddress
    ): TreasuryWallet {
        $normalized = trim($destinationAddress);

        $treasury = TreasuryWallet::where('network_id', $network->id)
            ->where('wallet_address', $normalized)
            ->where('status', 'ACTIVE')
            ->where('is_allowlisted', true)
            ->first();

        if (!$treasury) {
            throw new InvalidArgumentException(
                "Destination address [{$normalized}] is not an active allowlisted treasury wallet for [{$network->network_code}]."
            );
        }

        if ((bool) $treasury->is_testnet !== (bool) $network->is_testnet) {
            throw new InvalidArgumentException(
                "Network environment mismatch: cannot sweep between testnet and mainnet for [{$network->network_code}]."
            );
        }

        return $treasury;
    }
}

