<?php

namespace App\Services\Security;

use App\Models\Wallet\BlockchainNetwork;
use RuntimeException;

/**
 * Modular Fordefi MPC Custody Adapter skeleton.
 * Requires verified Fordefi API token and signer key before activation.
 */
class FordefiCustodyAdapter implements CustodyProviderInterface
{
    public function providerKey(): string
    {
        return 'fordefi';
    }

    public function derivePublicAddress(BlockchainNetwork $network, string $derivationPath, int $index): string
    {
        $this->assertConfigured();
        throw new RuntimeException('Fordefi live API integration requires sandbox credential verification before use.');
    }

    public function signTransaction(array $payload): array
    {
        $this->assertConfigured();
        throw new RuntimeException('Fordefi live signing requires sandbox credential verification before use.');
    }

    public function healthCheck(): array
    {
        $configured = !empty(config('custody.fordefi.api_token')) && !empty(config('custody.fordefi.vault_id'));
        return [
            'ok' => false,
            'provider' => $this->providerKey(),
            'message' => $configured ? 'Credentials set; awaiting sandbox verification' : 'FORDEFI_API_TOKEN / FORDEFI_VAULT_ID not configured',
        ];
    }

    protected function assertConfigured(): void
    {
        if (empty(config('custody.fordefi.api_token')) || empty(config('custody.fordefi.vault_id'))) {
            throw new RuntimeException('Fordefi adapter is not configured (missing FORDEFI_API_TOKEN or FORDEFI_VAULT_ID).');
        }
    }
}

