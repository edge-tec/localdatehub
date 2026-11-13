<?php

namespace App\Services\Security;

use App\Models\Wallet\BlockchainNetwork;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class HsmMpcHttpCustodyProvider implements CustodyProviderInterface
{
    public function providerKey(): string
    {
        return 'hsm_http';
    }

    public function derivePublicAddress(BlockchainNetwork $network, string $derivationPath, int $index): string
    {
        $response = $this->signedRequest('POST', '/v1/addresses/derive', [
            'network_code' => $network->network_code,
            'network_type' => $network->network_type,
            'derivation_path' => $derivationPath,
            'index' => $index,
        ]);

        if (empty($response['address'])) {
            throw new RuntimeException("HSM/MPC did not return a valid public address for [{$network->network_code}].");
        }

        return (string) $response['address'];
    }

    public function signTransaction(array $payload): array
    {
        $response = $this->signedRequest('POST', '/v1/transactions/sign', $payload);

        if (empty($response['signed_tx']) && empty($response['tx_hash'])) {
            throw new RuntimeException('HSM/MPC signing response missing signed_tx/tx_hash.');
        }

        return [
            'signed_tx' => (string) ($response['signed_tx'] ?? ''),
            'tx_hash' => isset($response['tx_hash']) ? (string) $response['tx_hash'] : null,
            'custody_request_id' => (string) ($response['request_id'] ?? Str::uuid()),
        ];
    }

    /**
     * Request gas sponsorship transaction signing from the isolated gas sponsor wallet.
     *
     * @param array<string, mixed> $payload
     * @return array{signed_tx: string, tx_hash: string|null, custody_request_id: string}
     */
    public function sponsorGas(array $payload): array
    {
        $response = $this->signedRequest('POST', '/v1/gas/sponsor', $payload);

        if (empty($response['signed_tx']) && empty($response['tx_hash'])) {
            throw new RuntimeException('Isolated signer gas sponsorship response missing signed_tx/tx_hash.');
        }

        return [
            'signed_tx' => (string) ($response['signed_tx'] ?? ''),
            'tx_hash' => isset($response['tx_hash']) ? (string) $response['tx_hash'] : null,
            'custody_request_id' => (string) ($response['request_id'] ?? Str::uuid()),
        ];
    }

    public function healthCheck(): array
    {
        $baseUrl = (string) config('custody.hsm_http.base_url');
        if ($baseUrl === '') {
            return ['ok' => false, 'provider' => $this->providerKey(), 'message' => 'HSM_HTTP_BASE_URL not configured'];
        }

        try {
            $res = $this->signedRequest('GET', '/v1/health', []);
            return [
                'ok' => (bool) ($res['ok'] ?? true),
                'provider' => $this->providerKey(),
                'message' => (string) ($res['status'] ?? 'healthy'),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'provider' => $this->providerKey(), 'message' => $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    protected function signedRequest(string $method, string $path, array $body): array
    {
        $cfg = config('custody.hsm_http');
        $baseUrl = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        $hmacSecret = (string) ($cfg['hmac_secret'] ?? '');

        if ($baseUrl === '' || $hmacSecret === '') {
            throw new RuntimeException('HSM/MPC HTTP custody credentials (HSM_HTTP_BASE_URL / HSM_HTTP_HMAC_SECRET) are not configured.');
        }

        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $jsonBody = $method === 'GET' ? '' : json_encode($body, JSON_THROW_ON_ERROR);
        $canonical = "{$timestamp}.{$nonce}.{$method}.{$path}.{$jsonBody}";
        $signature = hash_hmac('sha256', $canonical, $hmacSecret);

        $client = $this->buildMtlsClient($cfg)->withHeaders([
            'X-Custody-Key' => (string) ($cfg['api_key'] ?? ''),
            'X-Custody-Timestamp' => $timestamp,
            'X-Custody-Nonce' => $nonce,
            'X-Custody-Signature' => $signature,
            'Accept' => 'application/json',
        ]);

        $url = $baseUrl . $path;
        $response = $method === 'GET'
            ? $client->get($url)
            : $client->withBody($jsonBody, 'application/json')->post($url);

        if (!$response->successful()) {
            throw new RuntimeException("HSM/MPC HTTP error [{$response->status()}]: {$response->body()}");
        }

        return (array) $response->json();
    }

    /**
     * @param array<string, mixed> $cfg
     */
    protected function buildMtlsClient(array $cfg): PendingRequest
    {
        $options = [];
        if (!empty($cfg['mtls_cert_path'])) {
            $options['cert'] = $cfg['mtls_cert_path'];
        }
        if (!empty($cfg['mtls_key_path'])) {
            $options['ssl_key'] = $cfg['mtls_key_path'];
        }
        if (!empty($cfg['mtls_ca_path'])) {
            $options['verify'] = $cfg['mtls_ca_path'];
        }

        return Http::timeout((int) ($cfg['timeout_seconds'] ?? 15))->withOptions($options);
    }
}

