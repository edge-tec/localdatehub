<?php

namespace App\Services\Blockchain;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;

interface BlockchainAdapterInterface
{
    public function supportsNetworkType(string $networkType): bool;

    public function validateAddress(string $address, BlockchainNetwork $network): bool;

    public function getLatestBlockHeight(BlockchainNetwork $network): int;

    public function getBlockHash(BlockchainNetwork $network, int $height): ?string;

    /**
     * Fetch incoming transactions for a deposit address or block window.
     *
     * @return array<int, array{
     *     tx_hash: string,
     *     tx_index: int,
     *     block_height: int|null,
     *     block_hash: string|null,
     *     from_address: string,
     *     to_address: string,
     *     contract_address: string|null,
     *     amount_base_units: string,
     *     confirmations: int,
     *     is_reverted: bool
     * }>
     */
    public function fetchIncomingTransactions(
        BlockchainNetwork $network,
        CryptoAsset $asset,
        string $address,
        int $fromHeight = 0
    ): array;

    /**
     * Independently verify a transaction hash on-chain.
     *
     * @return array{
     *     exists: bool,
     *     confirmations: int,
     *     block_height: int|null,
     *     block_hash: string|null,
     *     from_address: string|null,
     *     to_address: string|null,
     *     contract_address: string|null,
     *     amount_base_units: string|null,
     *     is_reverted: bool
     * }
     */
    public function verifyTransaction(
        BlockchainNetwork $network,
        CryptoAsset $asset,
        string $txHash
    ): array;

    public function getAddressBalanceBaseUnits(
        BlockchainNetwork $network,
        CryptoAsset $asset,
        string $address
    ): string;

    public function estimateSweepFeeBaseUnits(
        BlockchainNetwork $network,
        CryptoAsset $asset
    ): string;

    public function broadcastSignedTransaction(
        BlockchainNetwork $network,
        string $signedTx
    ): string;
}

