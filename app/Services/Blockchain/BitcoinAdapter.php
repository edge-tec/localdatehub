<?php

namespace App\Services\Blockchain;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BitcoinAdapter implements BlockchainAdapterInterface
{
    public function supportsNetworkType(string $networkType): bool
    {
        return strtolower($networkType) === 'utxo';
    }

    public function validateAddress(string $address, BlockchainNetwork $network): bool
    {
        $trimmed = trim($address);

        return match (strtoupper($network->network_code)) {
            'BTC' => (bool) preg_match('/^(bc1[qpzry9x8gf2tvdw0s3jn54khce6mua7l]{25,87}|[13][a-km-zA-HJ-NP-Z1-9]{25,34})$/', $trimmed),
            'LTC' => (bool) preg_match('/^(ltc1[qpzry9x8gf2tvdw0s3jn54khce6mua7l]{25,87}|[LM3][a-km-zA-HJ-NP-Z1-9]{25,34})$/', $trimmed),
            'DOGE' => (bool) preg_match('/^[DA9][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $trimmed),
            default => false,
        };
    }

    public function getLatestBlockHeight(BlockchainNetwork $network): int
    {
        $base = $this->baseUrl($network);
        $res = Http::timeout(8)->get("{$base}/blocks/tip/height");
        return $res->successful() ? (int) trim($res->body()) : 0;
    }

    public function getBlockHash(BlockchainNetwork $network, int $height): ?string
    {
        $base = $this->baseUrl($network);
        $res = Http::timeout(8)->get("{$base}/block-height/{$height}");
        return $res->successful() ? trim($res->body()) : null;
    }

    public function fetchIncomingTransactions(
        BlockchainNetwork $network,
        CryptoAsset $asset,
        string $address,
        int $fromHeight = 0
    ): array {
        $base = $this->baseUrl($network);
        $res = Http::timeout(10)->get("{$base}/address/{$address}/txs");
        if (!$res->successful() || !is_array($res->json())) {
            return [];
        }

        $tip = $this->getLatestBlockHeight($network);
        $items = [];
        foreach ($res->json() as $tx) {
            $blockHeight = $tx['status']['block_height'] ?? null;
            if ($blockHeight !== null && $fromHeight > 0 && $blockHeight < $fromHeight) {
                continue;
            }
            $sats = 0;
            foreach ($tx['vout'] ?? [] as $vout) {
                if (($vout['scriptpubkey_address'] ?? '') === $address) {
                    $sats += (int) ($vout['value'] ?? 0);
                }
            }
            if ($sats <= 0) {
                continue;
            }
            $confs = ($blockHeight !== null && $tip >= $blockHeight) ? ($tip - $blockHeight + 1) : 0;
            $items[] = [
                'tx_hash' => (string) $tx['txid'],
                'tx_index' => 0,
                'block_height' => $blockHeight,
                'block_hash' => $tx['status']['block_hash'] ?? null,
                'from_address' => (string) ($tx['vin'][0]['prevout']['scriptpubkey_address'] ?? 'utxo_input'),
                'to_address' => $address,
                'contract_address' => null,
                'amount_base_units' => (string) $sats,
                'confirmations' => $confs,
                'is_reverted' => false,
            ];
        }

        return $items;
    }

    public function verifyTransaction(BlockchainNetwork $network, CryptoAsset $asset, string $txHash): array
    {
        $base = $this->baseUrl($network);
        $res = Http::timeout(10)->get("{$base}/tx/{$txHash}");
        if (!$res->successful() || !is_array($res->json())) {
            return [
                'exists' => false,
                'confirmations' => 0,
                'block_height' => null,
                'block_hash' => null,
                'from_address' => null,
                'to_address' => null,
                'contract_address' => null,
                'amount_base_units' => null,
                'is_reverted' => false,
            ];
        }

        $tx = $res->json();
        $blockHeight = $tx['status']['block_height'] ?? null;
        $tip = $blockHeight !== null ? $this->getLatestBlockHeight($network) : 0;
        $confs = ($blockHeight !== null && $tip >= $blockHeight) ? ($tip - $blockHeight + 1) : 0;

        return [
            'exists' => true,
            'confirmations' => $confs,
            'block_height' => $blockHeight,
            'block_hash' => $tx['status']['block_hash'] ?? null,
            'from_address' => (string) ($tx['vin'][0]['prevout']['scriptpubkey_address'] ?? 'utxo_input'),
            'to_address' => (string) ($tx['vout'][0]['scriptpubkey_address'] ?? ''),
            'contract_address' => null,
            'amount_base_units' => (string) ($tx['vout'][0]['value'] ?? '0'),
            'is_reverted' => false,
        ];
    }

    public function getAddressBalanceBaseUnits(BlockchainNetwork $network, CryptoAsset $asset, string $address): string
    {
        $base = $this->baseUrl($network);
        $res = Http::timeout(8)->get("{$base}/address/{$address}");
        if (!$res->successful() || !is_array($res->json())) {
            return '0';
        }
        $stats = $res->json()['chain_stats'] ?? [];
        $funded = (int) ($stats['funded_txo_sum'] ?? 0);
        $spent = (int) ($stats['spent_txo_sum'] ?? 0);

        return (string) max(0, $funded - $spent);
    }

    public function estimateSweepFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string
    {
        return '2500'; // Conservative 2500 satoshis/litoshis UTXO sweep baseline
    }

    public function broadcastSignedTransaction(BlockchainNetwork $network, string $signedTx): string
    {
        $base = $this->baseUrl($network);
        $res = Http::timeout(10)->withBody($signedTx, 'text/plain')->post("{$base}/tx");
        if (!$res->successful()) {
            throw new RuntimeException("UTXO broadcast failed: {$res->body()}");
        }

        return trim($res->body());
    }

    protected function baseUrl(BlockchainNetwork $network): string
    {
        $first = trim(explode(',', (string) $network->rpc_configuration_reference)[0] ?? '');
        return $first !== '' ? rtrim($first, '/') : 'https://mempool.space/api';
    }
}

