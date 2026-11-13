<?php

namespace App\Services\Blockchain;

use App\Models\Wallet\BlockchainCheckpoint;
use App\Models\Wallet\BlockchainNetwork;
use InvalidArgumentException;

class BlockchainMonitor
{
    public function adapterFor(BlockchainNetwork $network): BlockchainAdapterInterface
    {
        $code = strtoupper($network->network_code);
        $type = strtolower($network->network_type);

        return match (true) {
            $code === 'BTC' => app(BitcoinAdapter::class),
            $code === 'LTC' => app(LitecoinAdapter::class),
            $code === 'DOGE' => app(DogecoinAdapter::class),
            $type === 'evm' => app(EvmAdapter::class),
            $type === 'tron' => app(TronAdapter::class),
            $type === 'solana' => app(SolanaAdapter::class),
            $type === 'xrp' => app(XrpAdapter::class),
            default => throw new InvalidArgumentException("No blockchain adapter registered for [{$network->network_code}]."),
        };
    }

    public function updateCheckpoint(BlockchainNetwork $network, int $height, ?string $blockHash = null): BlockchainCheckpoint
    {
        return BlockchainCheckpoint::updateOrCreate(
            ['network_id' => $network->id],
            [
                'last_processed_height' => $height,
                'last_processed_hash' => $blockHash,
                'last_processed_at' => now(),
            ]
        );
    }
}

