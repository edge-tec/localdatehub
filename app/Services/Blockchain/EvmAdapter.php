<?php

namespace App\Services\Blockchain;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use RuntimeException;

class EvmAdapter implements BlockchainAdapterInterface
{
    public function __construct(
        protected RpcFailoverService $rpc
    ) {}

    public function supportsNetworkType(string $networkType): bool
    {
        return strtolower($networkType) === 'evm';
    }

    public function validateAddress(string $address, BlockchainNetwork $network): bool
    {
        return (bool) preg_match('/^0x[a-fA-F0-9]{40}$/', trim($address));
    }

    public function getLatestBlockHeight(BlockchainNetwork $network): int
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'eth_blockNumber');
        return isset($res['result']) ? (int) hexdec((string) $res['result']) : 0;
    }

    public function getBlockHash(BlockchainNetwork $network, int $height): ?string
    {
        $hex = '0x' . dechex($height);
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'eth_getBlockByNumber', [$hex, false]);
        return $res['result']['hash'] ?? null;
    }

    public function fetchIncomingTransactions(
        BlockchainNetwork $network,
        CryptoAsset $asset,
        string $address,
        int $fromHeight = 0
    ): array {
        $endpoints = $this->endpoints($network);
        $latest = $this->getLatestBlockHeight($network);
        if ($latest <= 0) {
            return [];
        }

        if (empty($asset->contract_address)) {
            return $this->fetchIncomingNativeTransactions($endpoints, $address, $fromHeight, $latest);
        }

        $startBlock = $fromHeight > 0 ? $fromHeight : max(0, $latest - 500);
        $transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';
        $paddedTo = '0x000000000000000000000000' . strtolower(ltrim(trim($address), '0x'));

        $res = $this->rpc->jsonRpc($endpoints, 'eth_getLogs', [[
            'fromBlock' => '0x' . dechex($startBlock),
            'toBlock' => '0x' . dechex($latest),
            'address' => $asset->contract_address,
            'topics' => [$transferTopic, null, $paddedTo],
        ]]);

        $logs = $res['result'] ?? [];
        if (!is_array($logs)) {
            return [];
        }

        $items = [];
        foreach ($logs as $log) {
            $blockHeight = isset($log['blockNumber']) ? (int) hexdec((string) $log['blockNumber']) : null;
            $fromTopic = (string) ($log['topics'][1] ?? '');
            $fromAddress = strlen($fromTopic) >= 42 ? '0x' . substr($fromTopic, -40) : '';
            $amountHex = (string) ($log['data'] ?? '0x0');
            $amount = $this->hexToDec($amountHex);
            $confs = ($blockHeight !== null && $latest >= $blockHeight) ? ($latest - $blockHeight + 1) : 0;

            $items[] = [
                'tx_hash' => (string) ($log['transactionHash'] ?? ''),
                'tx_index' => isset($log['logIndex']) ? (int) hexdec((string) $log['logIndex']) : 0,
                'block_height' => $blockHeight,
                'block_hash' => $log['blockHash'] ?? null,
                'from_address' => $fromAddress,
                'to_address' => $address,
                'contract_address' => $asset->contract_address,
                'amount_base_units' => $amount,
                'confirmations' => $confs,
                'is_reverted' => false,
            ];
        }

        return $items;
    }

    /**
     * Scan recent blocks for native EVM coin transfers (ETH, BNB, POL) to a deposit address.
     *
     * @param array<int, string> $endpoints
     * @return array<int, array<string, mixed>>
     */
    protected function fetchIncomingNativeTransactions(
        array $endpoints,
        string $address,
        int $fromHeight,
        int $latest
    ): array {
        $startBlock = $fromHeight > 0 ? max($fromHeight, $latest - 20) : max(0, $latest - 15);
        $target = strtolower(trim($address));
        $items = [];

        for ($height = $startBlock; $height <= $latest; $height++) {
            $blockRes = $this->rpc->jsonRpc($endpoints, 'eth_getBlockByNumber', ['0x' . dechex($height), true]);
            $block = $blockRes['result'] ?? null;
            if (!is_array($block) || empty($block['transactions']) || !is_array($block['transactions'])) {
                continue;
            }

            foreach ($block['transactions'] as $tx) {
                if (!is_array($tx) || empty($tx['to'])) {
                    continue;
                }
                if (strtolower((string) $tx['to']) !== $target) {
                    continue;
                }

                $amount = $this->hexToDec((string) ($tx['value'] ?? '0x0'));
                if ($amount === '0') {
                    continue;
                }

                $items[] = [
                    'tx_hash' => (string) ($tx['hash'] ?? ''),
                    'tx_index' => isset($tx['transactionIndex']) ? (int) hexdec((string) $tx['transactionIndex']) : 0,
                    'block_height' => $height,
                    'block_hash' => $block['hash'] ?? null,
                    'from_address' => (string) ($tx['from'] ?? ''),
                    'to_address' => $address,
                    'contract_address' => null,
                    'amount_base_units' => $amount,
                    'confirmations' => max(1, $latest - $height + 1),
                    'is_reverted' => false,
                ];
            }
        }

        return $items;
    }

    protected function hexToDec(string $hex): string
    {
        $clean = ltrim(strtolower($hex), '0x');
        if ($clean === '') {
            return '0';
        }
        $dec = '0';
        $len = strlen($clean);
        for ($i = 0; $i < $len; $i++) {
            $digit = (string) hexdec($clean[$i]);
            $dec = function_exists('bcmul') ? bcadd(bcmul($dec, '16', 0), $digit, 0) : (string) ((int) $dec * 16 + (int) $digit);
        }

        return $dec;
    }

    public function verifyTransaction(
        BlockchainNetwork $network,
        CryptoAsset $asset,
        string $txHash
    ): array {
        $endpoints = $this->endpoints($network);
        $txRes = $this->rpc->jsonRpc($endpoints, 'eth_getTransactionByHash', [$txHash]);
        $tx = $txRes['result'] ?? null;
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

        $receiptRes = $this->rpc->jsonRpc($endpoints, 'eth_getTransactionReceipt', [$txHash]);
        $receipt = $receiptRes['result'] ?? null;
        $blockHeight = isset($tx['blockNumber']) ? (int) hexdec((string) $tx['blockNumber']) : null;
        $latest = $blockHeight !== null ? $this->getLatestBlockHeight($network) : 0;
        $confirmations = ($blockHeight !== null && $latest >= $blockHeight) ? ($latest - $blockHeight + 1) : 0;
        $reverted = is_array($receipt) && (($receipt['status'] ?? '0x1') === '0x0');

        if (!empty($asset->contract_address) && is_array($receipt) && !empty($receipt['logs']) && is_array($receipt['logs'])) {
            $transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';
            $expectedContract = strtolower(trim($asset->contract_address));

            foreach ($receipt['logs'] as $log) {
                if (strtolower((string) ($log['address'] ?? '')) !== $expectedContract) {
                    continue;
                }
                $topics = $log['topics'] ?? [];
                if (strtolower((string) ($topics[0] ?? '')) !== $transferTopic || count($topics) < 3) {
                    continue;
                }

                $fromAddr = '0x' . substr((string) $topics[1], -40);
                $toAddr = '0x' . substr((string) $topics[2], -40);
                $amount = $this->hexToDec((string) ($log['data'] ?? '0x0'));

                return [
                    'exists' => true,
                    'confirmations' => $confirmations,
                    'block_height' => $blockHeight,
                    'block_hash' => $tx['blockHash'] ?? null,
                    'from_address' => $fromAddr,
                    'to_address' => $toAddr,
                    'contract_address' => $asset->contract_address,
                    'amount_base_units' => $amount,
                    'is_reverted' => $reverted,
                ];
            }
        }

        return [
            'exists' => true,
            'confirmations' => $confirmations,
            'block_height' => $blockHeight,
            'block_hash' => $tx['blockHash'] ?? null,
            'from_address' => $tx['from'] ?? null,
            'to_address' => $tx['to'] ?? null,
            'contract_address' => $asset->contract_address,
            'amount_base_units' => isset($tx['value']) ? $this->hexToDec((string) $tx['value']) : '0',
            'is_reverted' => $reverted,
        ];
    }

    public function getAddressBalanceBaseUnits(
        BlockchainNetwork $network,
        CryptoAsset $asset,
        string $address
    ): string {
        if (!empty($asset->contract_address)) {
            $data = '0x70a08231000000000000000000000000' . strtolower(ltrim(trim($address), '0x'));
            $res = $this->rpc->jsonRpc($this->endpoints($network), 'eth_call', [[
                'to' => $asset->contract_address,
                'data' => $data,
            ], 'latest']);

            return isset($res['result']) ? $this->hexToDec((string) $res['result']) : '0';
        }

        $res = $this->rpc->jsonRpc($this->endpoints($network), 'eth_getBalance', [$address, 'latest']);
        return isset($res['result']) ? $this->hexToDec((string) $res['result']) : '0';
    }

    public function estimateSweepFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string
    {
        return $this->getEip1559FeeParams($network, $asset)['total_max_fee_base_units'];
    }

    /**
     * Compute EIP-1559 fee parameters and worst-case fee budget in base units.
     *
     * @return array{gas_limit: string, max_fee_per_gas: string, max_priority_fee_per_gas: string, total_max_fee_base_units: string}
     */
    public function getEip1559FeeParams(
        BlockchainNetwork $network,
        CryptoAsset $asset,
        ?string $fromAddress = null,
        ?string $toAddress = null
    ): array {
        $endpoints = $this->endpoints($network);
        $res = $this->rpc->jsonRpc($endpoints, 'eth_gasPrice');
        $gasPriceWei = isset($res['result']) ? (int) hexdec((string) $res['result']) : 5000000000;
        $gasLimit = $asset->contract_address ? 65000 : 21000;

        if ($fromAddress && $toAddress && empty($asset->contract_address)) {
            $estRes = $this->rpc->jsonRpc($endpoints, 'eth_estimateGas', [[
                'from' => $fromAddress,
                'to' => $toAddress,
                'value' => '0x1',
            ]]);
            if (!empty($estRes['result'])) {
                $estimated = (int) hexdec((string) $estRes['result']);
                if ($estimated > 21000) {
                    $gasLimit = (int) ceil($estimated * 1.25);
                }
            }
        }

        $priorityFee = max(100000000, min($gasPriceWei, 1500000000));
        $maxFeePerGas = max($gasPriceWei * 2, $priorityFee + 1000000000);
        $totalMaxFee = function_exists('bcmul')
            ? bcmul((string) $gasLimit, (string) $maxFeePerGas, 0)
            : (string) ($gasLimit * $maxFeePerGas);

        return [
            'gas_limit' => (string) $gasLimit,
            'max_fee_per_gas' => (string) $maxFeePerGas,
            'max_priority_fee_per_gas' => (string) $priorityFee,
            'total_max_fee_base_units' => $totalMaxFee,
        ];
    }

    public function getNextNonce(BlockchainNetwork $network, string $address): int
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'eth_getTransactionCount', [$address, 'pending']);

        return isset($res['result']) ? (int) hexdec((string) $res['result']) : 0;
    }

    public function broadcastSignedTransaction(BlockchainNetwork $network, string $signedTx): string
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'eth_sendRawTransaction', [$signedTx]);
        if (empty($res['result'])) {
            throw new RuntimeException("Failed to broadcast EVM transaction on [{$network->network_code}].");
        }

        return (string) $res['result'];
    }

    /**
     * @return array<int, string>
     */
    protected function endpoints(BlockchainNetwork $network): array
    {
        return array_values(array_filter(explode(',', (string) $network->rpc_configuration_reference)));
    }
}

