<?php

namespace App\Services\Security;

use App\Models\Wallet\BlockchainNetwork;
use RuntimeException;

/**
 * Modular BitGo Custody Adapter skeleton.
 * Requires verified BitGo access token and enterprise ID before activation.
 */
class BitGoCustodyAdapter implements CustodyProviderInterface
{
    public function providerKey(): string
    {
        return 'bitgo';
    }

    public function derivePublicAddress(BlockchainNetwork $network, string $derivationPath, int $index): string
    {
        $this->assertConfigured();
        throw new RuntimeException('BitGo live API integration requires sandbox credential verification before use.');
    }

    public function signTransaction(array $payload): array
    {
        $this->assertConfigured();
        throw new RuntimeException('BitGo live signing requires sandbox credential verification before use.');
    }

    public function healthCheck(): array
    {
        $configured = !empty(config('custody.bitgo.access_token')) && !empty(config('custody.bitgo.enterprise_id'));
        return [
            'ok' => false,
            'provider' => $this->providerKey(),
            'message' => $configured ? 'Credentials set; awaiting sandbox verification' : 'BITGO_ACCESS_TOKEN / BITGO_ENTERPRISE_ID not configured',
        ];
    }

    protected function assertConfigured(): void
    {
        if (empty(config('custody.bitgo.access_token')) || empty(config('custody.bitgo.enterprise_id'))) {
            throw new RuntimeException('BitGo adapter is not configured (missing BITGO_ACCESS_TOKEN or BITGO_ENTERPRISE_ID).');
        }
    }
}

