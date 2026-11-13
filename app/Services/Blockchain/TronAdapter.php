<?php

namespace App\Services\Blockchain;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TronAdapter implements BlockchainAdapterInterface
{
    public function supportsNetworkType(string $networkType): bool
    {
        return strtolower($networkType) === 'tron';
    }

    public function validateAddress(string $address, BlockchainNetwork $network): bool
    {
        return (bool) preg_match('/^T[1-9A-HJ-NP-Za-km-z]{33}$/', trim($address));
    }

    public function getLatestBlockHeight(BlockchainNetwork $network): int
    {
        $base = $this->baseUrl($network);
        $res = Http::timeout(8)->post("{$base}/wallet/getnowblock");
        return (int) ($res->json()['block_header']['raw_data']['number'] ?? 0);
    }

    public function getBlockHash(BlockchainNetwork $network, int $height): ?string
    {
        $base = $this->baseUrl($network);
        $res = Http::timeout(8)->post("{$base}/wallet/getblockbynum", ['num' => $height]);
        return $res->json()['blockID'] ?? null;
    }

    public function fetchIncomingTransactions(BlockchainNetwork $network, CryptoAsset $asset, string $address, int $fromHeight = 0): array
    {
        return [];
    }

    public function verifyTransaction(BlockchainNetwork $network, CryptoAsset $asset, string $txHash): array
    {
        $base = $this->baseUrl($network);
        $res = Http::timeout(8)->post("{$base}/wallet/gettransactioninfobyid", ['value' => $txHash]);
        $info = $res->json();
        if (!is_array($info) || empty($info['id'])) {
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

        $height = isset($info['blockNumber']) ? (int) $info['blockNumber'] : null;
        $tip = $height ? $this->getLatestBlockHeight($network) : 0;
        $confs = ($height && $tip >= $height) ? ($tip - $height + 1) : 0;
        $reverted = isset($info['receipt']['result']) && $info['receipt']['result'] !== 'SUCCESS';

        return [
            'exists' => true,
            'confirmations' => $confs,
            'block_height' => $height,
            'block_hash' => null,
            'from_address' => null,
            'to_address' => null,
            'contract_address' => $asset->contract_address,
            'amount_base_units' => null,
            'is_reverted' => $reverted,
        ];
    }

    public function getAddressBalanceBaseUnits(BlockchainNetwork $network, CryptoAsset $asset, string $address): string
    {
        return '0';
    }

    public function estimateSweepFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string
    {
        return $asset->contract_address ? '15000000' : '1100000'; // 15 TRX energy or 1.1 TRX bandwidth in SUN
    }

    public function broadcastSignedTransaction(BlockchainNetwork $network, string $signedTx): string
    {
        $base = $this->baseUrl($network);
        $res = Http::timeout(8)->withBody($signedTx, 'application/json')->post("{$base}/wallet/broadcasttransaction");
        $json = $res->json();
        if (empty($json['result'])) {
            throw new RuntimeException('TRON broadcast failed: ' . json_encode($json));
        }

        return (string) ($json['txid'] ?? '');
    }

    protected function baseUrl(BlockchainNetwork $network): string
    {
        $first = trim(explode(',', (string) $network->rpc_configuration_reference)[0] ?? '');
        return $first !== '' ? rtrim($first, '/') : 'https://api.trongrid.io';
    }
}

