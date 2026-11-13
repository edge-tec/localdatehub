<?php

namespace App\Services\Security;

use App\Models\Wallet\BlockchainNetwork;

interface CustodyProviderInterface
{
    public function providerKey(): string;

    /**
     * Derive a public deposit address from the external custody/HSM/MPC vault.
     */
    public function derivePublicAddress(
        BlockchainNetwork $network,
        string $derivationPath,
        int $index
    ): string;

    /**
     * Request an external HSM/MPC signature and/or broadcast payload for an outbound sweep.
     *
     * @param array<string, mixed> $payload
     * @return array{signed_tx: string, tx_hash: string|null, custody_request_id: string}
     */
    public function signTransaction(array $payload): array;

    /**
     * Check custody service health and connectivity.
     *
     * @return array{ok: bool, provider: string, message: string}
     */
    public function healthCheck(): array;
}

