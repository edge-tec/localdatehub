<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CryptoSignerService
{
    public function isAvailable(): bool
    {
        return file_exists(base_path('scripts/crypto-signer.mjs'))
            && is_dir(base_path('node_modules/ethers'));
    }

    public function deriveEvmAddress(string $secret, int $index): ?string
    {
        $res = $this->deriveEvmAddressWithDetails($secret, $index);
        return ($res['success'] ?? false) && !empty($res['address']) ? (string) $res['address'] : null;
    }

    public function deriveEvmAddressWithDetails(string $secret, int $index): array
    {
        return $this->runScript([
            'action' => 'derive',
            'secret' => $secret,
            'index' => $index,
        ], 10);
    }

    public function sweepEvmDeposit(
        string $secret,
        int $index,
        string $toAddress,
        array $rpcUrls,
        ?string $contractAddress = null
    ): array {
        return $this->runScript([
            'action' => 'sweep',
            'secret' => $secret,
            'index' => $index,
            'to_address' => $toAddress,
            'rpc_urls' => array_values(array_filter($rpcUrls)),
            'contract_address' => $contractAddress,
        ], 45);
    }

    protected function resolveNodeBinary(): string
    {
        $configured = trim((string) env('CRYPTO_NODE_BINARY', ''));
        if ($configured !== '' && is_executable($configured)) {
            return $configured;
        }

        $which = trim((string) @shell_exec('which node 2>/dev/null'));
        if ($which !== '' && is_executable($which)) {
            return $which;
        }

        $candidates = array_merge(
            ['/usr/local/bin/node', '/usr/bin/node', '/opt/homebrew/bin/node'],
            glob('/www/server/nodejs/*/bin/node') ?: [],
            glob('/root/.nvm/versions/node/*/bin/node') ?: [],
            glob('/home/*/.nvm/versions/node/*/bin/node') ?: []
        );

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'node';
    }

    protected function runScript(array $payload, int $timeoutSeconds = 20): array
    {
        if (!$this->isAvailable()) {
            return ['success' => false, 'error' => 'Node ethers signer is not installed (run npm install ethers).'];
        }

        try {
            $nodeBin = $this->resolveNodeBinary();
            $process = new Process([$nodeBin, base_path('scripts/crypto-signer.mjs')], base_path());
            $process->setInput(json_encode($payload));
            $process->setTimeout($timeoutSeconds);
            $process->run();

            $output = trim($process->getOutput());
            $decoded = json_decode($output, true);

            if (is_array($decoded)) {
                return $decoded;
            }

            return [
                'success' => false,
                'error' => trim($process->getErrorOutput()) ?: 'Invalid signer response.',
            ];
        } catch (\Throwable $e) {
            Log::warning("CryptoSignerService execution failed: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

