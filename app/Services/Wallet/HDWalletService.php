<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\WalletAddress;

class HDWalletService
{
    public function __construct(
        protected AddressAllocator $allocator
    ) {}

    public function getOrAssignDepositAddress(
        User $user,
        BlockchainNetwork $network,
        CryptoAsset $asset
    ): WalletAddress {
        return $this->allocator->allocate($user, $network, $asset);
    }
}

