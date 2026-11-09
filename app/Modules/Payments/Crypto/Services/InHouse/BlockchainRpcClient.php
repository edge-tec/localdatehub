<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Modules\Payments\Crypto\Models\CryptoNetwork;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlockchainRpcClient
{
    /**
     * Query network RPC endpoint with automatic failover.
     */
    public function queryRpc(CryptoNetwork $network, string $method, array $params = []): ?array
    {
        $endpoints = array_filter([$network->rpc_endpoint_primary, $network->rpc_endpoint_fallback]);

        foreach ($endpoints as $url) {
            try {
                if (str_contains($url, 'tron')) {
                    // TRON API query
                    $response = Http::timeout(8)->post("{$url}/{$method}", $params);
                } elseif (str_contains($url, 'mempool') || str_contains($url, 'blockstream')) {
                    // Bitcoin REST API
                    $response = Http::timeout(8)->get("{$url}/{$method}");
                } else {
                    // EVM JSON-RPC
                    $response = Http::timeout(8)->post($url, [
                        'jsonrpc' => '2.0',
                        'method' => $method,
                        'params' => $params,
                        'id' => 1,
                    ]);
                }

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                Log::warning("RPC query to [{$url}] failed: {$e->getMessage()}");
            }
        }

        return null;
    }

    /**
     * Get current latest block height for network.
     */
    public function getLatestBlockNumber(CryptoNetwork $network): int
    {
        $res = $this->queryRpc($network, 'eth_blockNumber');
        if ($res && isset($res['result'])) {
            return hexdec($res['result']);
        }

        return 0;
    }
}

