<?php

namespace App\Services\Security;

use App\Models\Wallet\BlockchainNetwork;
use RuntimeException;

/**
 * Isolated test-only custody driver.
 * SECURITY: Never permitted when APP_ENV=production.
 */
class MockCustodyProvider implements CustodyProviderInterface
{
    public function __construct()
    {
        if (app()->isProduction()) {
            throw new RuntimeException('SECURITY VIOLATION: MockCustodyProvider cannot be instantiated in production.');
        }
    }

    public function providerKey(): string
    {
        return 'mock';
    }

    public function derivePublicAddress(BlockchainNetwork $network, string $derivationPath, int $index): string
    {
        $seed = hash('sha256', "TEST:{$network->network_code}:{$derivationPath}:{$index}");

        return match (strtoupper($network->network_type)) {
            'evm' => '0x' . substr($seed, 0, 40),
            'utxo' => match (strtoupper($network->network_code)) {
                'BTC' => 'bc1q' . substr(preg_replace('/[^a-z0-9]/', 'q', $seed), 0, 38),
                'LTC' => 'ltc1q' . substr(preg_replace('/[^a-z0-9]/', 'q', $seed), 0, 38),
                'DOGE' => 'D' . substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', 'A', base64_encode(hex2bin($seed)))), 0, 33),
                default => 'bc1q' . substr($seed, 0, 38),
            },
            'tron' => 'T' . substr(strtoupper(preg_replace('/[^A-Za-z1-9]/', 'A', base64_encode(hex2bin($seed)))), 0, 33),
            'solana' => substr(preg_replace('/[^1-9A-HJ-NP-Za-km-z]/', 'A', base64_encode(hex2bin($seed)) . $seed), 0, 44),
            'xrp' => 'r' . substr(preg_replace('/[^1-9A-HJ-NP-Za-km-z]/', 'a', base64_encode(hex2bin($seed)) . $seed), 0, 33),
            default => '0x' . substr($seed, 0, 40),
        };
    }

    public function signTransaction(array $payload): array
    {
        if (app()->isProduction()) {
            throw new RuntimeException('SECURITY VIOLATION: Mock signing blocked in production.');
        }

        $txHash = '0x' . hash('sha256', json_encode($payload) . microtime(true));

        return [
            'signed_tx' => 'mock_signed_' . substr($txHash, 2, 32),
            'tx_hash' => $txHash,
            'custody_request_id' => 'mock-req-' . substr($txHash, 2, 16),
        ];
    }

    public function sponsorGas(array $payload): array
    {
        return $this->signTransaction(array_merge($payload, ['intent' => 'gas_sponsor']));
    }

    public function healthCheck(): array
    {
        return ['ok' => true, 'provider' => 'mock', 'message' => 'Test-only mock custody ready'];
    }
}

