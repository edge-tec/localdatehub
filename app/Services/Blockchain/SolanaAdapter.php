<?php

namespace App\Services\Blockchain;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use RuntimeException;

class SolanaAdapter implements BlockchainAdapterInterface
{
    public function __construct(
        protected RpcFailoverService $rpc
    ) {}

    public function supportsNetworkType(string $networkType): bool
    {
        return strtolower($networkType) === 'solana';
    }

    public function validateAddress(string $address, BlockchainNetwork $network): bool
    {
        return (bool) preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', trim($address));
    }

    public function getLatestBlockHeight(BlockchainNetwork $network): int
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'getSlot');
        return (int) ($res['result'] ?? 0);
    }

    public function getBlockHash(BlockchainNetwork $network, int $height): ?string
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'getBlock', [$height, ['transactionDetails' => 'none']]);
        return $res['result']['blockhash'] ?? null;
    }

    public function fetchIncomingTransactions(BlockchainNetwork $network, CryptoAsset $asset, string $address, int $fromHeight = 0): array
    {
        return [];
    }

    public function verifyTransaction(BlockchainNetwork $network, CryptoAsset $asset, string $txHash): array
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'getTransaction', [$txHash, ['encoding' => 'jsonParsed', 'maxSupportedTransactionVersion' => 0]]);
        $tx = $res['result'] ?? null;
        if (!is_array($tx)) {
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

        $slot = isset($tx['slot']) ? (int) $tx['slot'] : null;
        $latest = $slot !== null ? $this->getLatestBlockHeight($network) : 0;
        $confs = ($slot !== null && $latest >= $slot) ? ($latest - $slot + 1) : 0;

        return [
            'exists' => true,
            'confirmations' => $confs,
            'block_height' => $slot,
            'block_hash' => $tx['transaction']['message']['recentBlockhash'] ?? null,
            'from_address' => null,
            'to_address' => null,
            'contract_address' => $asset->contract_address,
            'amount_base_units' => null,
            'is_reverted' => !empty($tx['meta']['err']),
        ];
    }

    public function getAddressBalanceBaseUnits(BlockchainNetwork $network, CryptoAsset $asset, string $address): string
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'getBalance', [$address]);
        return (string) ($res['result']['value'] ?? '0');
    }

    public function estimateSweepFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string
    {
        return '5000'; // 5000 lamports standard signature fee
    }

    public function broadcastSignedTransaction(BlockchainNetwork $network, string $signedTx): string
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'sendTransaction', [$signedTx, ['encoding' => 'base64']]);
        if (empty($res['result'])) {
            throw new RuntimeException('Solana sendTransaction failed.');
        }

        return (string) $res['result'];
    }

    /**
     * @return array<int, string>
     */
    protected function endpoints(BlockchainNetwork $network): array
    {
        $list = array_values(array_filter(explode(',', (string) $network->rpc_configuration_reference)));
        return !empty($list) ? $list : ['https://api.mainnet-beta.solana.com'];
    }
}

