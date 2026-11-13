<?php

namespace App\Services\Security;

use App\Models\Wallet\BlockchainNetwork;
use RuntimeException;

/**
 * Modular Fireblocks Custody Adapter skeleton.
 * Requires verified Fireblocks API credentials and RSA signing key before activation.
 */
class FireblocksCustodyAdapter implements CustodyProviderInterface
{
    public function providerKey(): string
    {
        return 'fireblocks';
    }

    public function derivePublicAddress(BlockchainNetwork $network, string $derivationPath, int $index): string
    {
        $this->assertConfigured();
        throw new RuntimeException('Fireblocks live API integration requires sandbox credential verification before use.');
    }

    public function signTransaction(array $payload): array
    {
        $this->assertConfigured();
        throw new RuntimeException('Fireblocks live signing requires sandbox credential verification before use.');
    }

    public function healthCheck(): array
    {
        $configured = !empty(config('custody.fireblocks.api_key')) && !empty(config('custody.fireblocks.vault_account_id'));
        return [
            'ok' => false,
            'provider' => $this->providerKey(),
            'message' => $configured ? 'Credentials set; awaiting sandbox verification' : 'FIREBLOCKS_API_KEY / FIREBLOCKS_VAULT_ACCOUNT_ID not configured',
        ];
    }

    protected function assertConfigured(): void
    {
        if (empty(config('custody.fireblocks.api_key')) || empty(config('custody.fireblocks.vault_account_id'))) {
            throw new RuntimeException('Fireblocks adapter is not configured (missing FIREBLOCKS_API_KEY or FIREBLOCKS_VAULT_ACCOUNT_ID).');
        }
    }
}

