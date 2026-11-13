<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class RpcFailoverService
{
    /**
     * Execute a callback against ordered RPC endpoints with automatic failover.
     *
     * @param array<int, string> $endpoints
     */
    public function execute(array $endpoints, callable $callback): mixed
    {
        $filtered = array_values(array_filter(array_map('trim', $endpoints)));
        if (empty($filtered)) {
            throw new RuntimeException('No RPC endpoints configured.');
        }

        $lastException = null;
        foreach ($filtered as $endpoint) {
            try {
                return $callback($endpoint);
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        throw new RuntimeException(
            'All RPC endpoints failed: ' . ($lastException?->getMessage() ?? 'unknown error'),
            0,
            $lastException
        );
    }

    /**
     * @param array<int, string> $endpoints
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>
     */
    public function jsonRpc(array $endpoints, string $method, array $params = []): array
    {
        return $this->execute($endpoints, function (string $url) use ($method, $params) {
            $res = Http::timeout(8)->post($url, [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => $method,
                'params' => $params,
            ]);

            if (!$res->successful()) {
                throw new RuntimeException("RPC HTTP {$res->status()} from {$url}");
            }

            $json = $res->json();
            if (!is_array($json) || isset($json['error'])) {
                throw new RuntimeException('JSON-RPC error: ' . json_encode($json['error'] ?? 'invalid response'));
            }

            return $json;
        });
    }
}

