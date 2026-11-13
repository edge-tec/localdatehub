<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Modules\Payments\Crypto\Models\CryptoTreasuryWallet;
use InvalidArgumentException;

class TreasuryWalletService
{
    /**
     * Get the active Temporary Collection Treasury wallet for a network.
     */
    public function getTemporaryTreasury(string $networkKey): ?CryptoTreasuryWallet
    {
        $key = strtoupper($networkKey);
        $wallet = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET)
            ->where('network_key', $key)
            ->where('is_active', true)
            ->first();

        if (!$wallet && in_array($key, ['BNB', 'BEP20', 'ETH', 'ERC20', 'POLYGON', 'MATIC', 'BASE', 'ARBITRUM'], true)) {
            $wallet = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET)
                ->whereIn('network_key', ['BEP20', 'ERC20', 'BNB', 'ETH'])
                ->where('is_active', true)
                ->first();
        }

        return $wallet;
    }

    /**
     * Get the active Main Treasury wallet for a network.
     */
    public function getMainTreasury(string $networkKey): ?CryptoTreasuryWallet
    {
        $key = strtoupper($networkKey);
        $wallet = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET)
            ->where('network_key', $key)
            ->where('is_active', true)
            ->first();

        if (!$wallet && in_array($key, ['BNB', 'BEP20', 'ETH', 'ERC20', 'POLYGON', 'MATIC', 'BASE', 'ARBITRUM'], true)) {
            $wallet = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET)
                ->whereIn('network_key', ['BEP20', 'ERC20', 'BNB', 'ETH'])
                ->where('is_active', true)
                ->first();
        }

        return $wallet;
    }

    /**
     * Validate destination address against configured Trust Wallets.
     */
    public function validateTreasuryDestination(string $networkKey, string $destinationAddress, string $tier): bool
    {
        $wallet = CryptoTreasuryWallet::where('tier', $tier)
            ->where('network_key', strtoupper($networkKey))
            ->where('public_receiving_address', trim($destinationAddress))
            ->where('is_active', true)
            ->first();

        return $wallet !== null;
    }

    /**
     * Configure or update a Trust Wallet treasury address.
     */
    public function setTreasuryAddress(string $tier, string $networkKey, string $address, string $walletName): CryptoTreasuryWallet
    {
        if (!in_array($tier, [CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET, CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET], true)) {
            throw new InvalidArgumentException("Invalid treasury tier: [{$tier}].");
        }

        return CryptoTreasuryWallet::updateOrCreate(
            [
                'tier' => $tier,
                'network_key' => strtoupper($networkKey),
            ],
            [
                'wallet_name' => $walletName,
                'public_receiving_address' => trim($address),
                'is_active' => true,
            ]
        );
    }
}

