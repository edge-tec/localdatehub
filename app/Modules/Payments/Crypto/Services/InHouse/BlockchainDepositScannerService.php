<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Modules\Payments\Crypto\Models\CryptoNetwork;
use App\Modules\Payments\Crypto\Models\CryptoPaymentInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlockchainDepositScannerService
{
    public function __construct(
        protected PaymentVerificationService $verificationService
    ) {}

    /**
     * Query the live blockchain for incoming funds on the invoice's deposit address
     * and process verification / balance crediting in real time.
     */
    public function scanAndVerify(CryptoPaymentInvoice $invoice): CryptoPaymentInvoice
    {
        if (in_array($invoice->status, [
            CryptoPaymentInvoice::STATUS_COMPLETED,
            CryptoPaymentInvoice::STATUS_EXPIRED,
            CryptoPaymentInvoice::STATUS_FAILED,
            CryptoPaymentInvoice::STATUS_REFUNDED,
        ], true)) {
            return $invoice;
        }

        $depositAddress = $invoice->depositAddress;
        $publicAddress = $depositAddress?->public_address;
        if (!$depositAddress || empty($publicAddress)) {
            return $invoice;
        }

        $network = $depositAddress->network
            ?? CryptoNetwork::where('network_key', $invoice->network_key)
                ->where('is_active', true)
                ->first();

        if (!$network) {
            return $invoice;
        }

        $netKey = strtoupper($network->network_key);

        try {
            if (in_array($netKey, ['TRC20', 'TRX'], true)) {
                $this->scanTron($invoice, $network, $publicAddress);
            } elseif (in_array($netKey, ['BTC', 'BITCOIN'], true)) {
                $this->scanBitcoin($invoice, $network, $publicAddress);
            } elseif ($network->chain_id !== null || in_array($netKey, ['BEP20', 'BNB', 'ERC20', 'ETH', 'POLYGON', 'MATIC', 'BASE', 'ARBITRUM'], true)) {
                $this->scanEvm($invoice, $network, $publicAddress);
            }
        } catch (\Throwable $e) {
            Log::warning("Real-time blockchain scan failed for invoice [{$invoice->invoice_number}] on [{$netKey}]: {$e->getMessage()}");
        }

        $fresh = $invoice->fresh();
        if ($fresh->expires_at && $fresh->expires_at->isPast() && $fresh->status === CryptoPaymentInvoice::STATUS_WAITING_FOR_PAYMENT) {
            $fresh->update(['status' => CryptoPaymentInvoice::STATUS_EXPIRED]);
            return $fresh->fresh();
        }

        return $fresh;
    }

    protected function scanEvm(CryptoPaymentInvoice $invoice, CryptoNetwork $network, string $publicAddress): void
    {
        $endpoints = $this->getEvmEndpoints($network);
        $latestBlock = $this->getEvmLatestBlock($endpoints);

        if ($latestBlock <= 0) {
            return;
        }

        $contractAddress = trim((string) $invoice->contract_address);
        $isToken = $contractAddress !== '';

        if ($isToken) {
            $decimals = $this->getEvmTokenDecimals($endpoints, $contractAddress, $invoice->depositAddress?->currency?->decimal_precision ?? 18);
            $rawBalance = $this->getEvmTokenBalance($endpoints, $contractAddress, $publicAddress);
            if ($rawBalance === null || bccomp($rawBalance, '0', 0) <= 0) {
                return;
            }

            $divisor = bcpow('10', (string) $decimals, 0);
            $receivedAmount = bcdiv($rawBalance, $divisor, 8);

            $txInfo = $this->findEvmTokenTransferLog($endpoints, $contractAddress, $publicAddress, $latestBlock);
            $txHash = $txInfo['txHash'] ?? $this->resolveOrCreateDetectionRef($invoice, $network->network_key, $latestBlock, $publicAddress);
            $fromAddress = $txInfo['fromAddress'] ?? '0xOnChainSender';
            $baseBlock = $txInfo['blockNumber'] ?? $this->rememberDetectedBlock($invoice, $latestBlock);
            $confirmations = max(1, $latestBlock - $baseBlock + 1);

            $this->verificationService->processDetectedTransaction(
                invoice: $invoice,
                txHash: $txHash,
                fromAddress: $fromAddress,
                toAddress: $publicAddress,
                amount: $receivedAmount,
                confirmations: $confirmations,
                tokenContract: $contractAddress,
                blockHeight: $latestBlock
            );

            return;
        }

        // Native EVM Coin (BNB, ETH, MATIC)
        $weiBalance = $this->getEvmNativeBalance($endpoints, $publicAddress);
        if ($weiBalance === null || bccomp($weiBalance, '0', 0) <= 0) {
            return;
        }

        $receivedAmount = bcdiv($weiBalance, '1000000000000000000', 8);

        // If we already have a real 0x txHash (66 chars), query its receipt for exact blockNumber
        $txHash = $invoice->tx_hash;
        $baseBlock = null;
        if ($txHash && preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            $receipt = $this->callEvmRpc($endpoints, 'eth_getTransactionReceipt', [$txHash]);
            if (!empty($receipt['blockNumber'])) {
                $baseBlock = (int) hexdec($receipt['blockNumber']);
            }
        }

        if (!$txHash) {
            $txHash = $this->resolveOrCreateDetectionRef($invoice, $network->network_key, $latestBlock, $publicAddress);
        }

        if ($baseBlock === null) {
            $baseBlock = $this->rememberDetectedBlock($invoice, $latestBlock);
        }

        $confirmations = max(1, $latestBlock - $baseBlock + 1);

        $this->verificationService->processDetectedTransaction(
            invoice: $invoice,
            txHash: $txHash,
            fromAddress: '0xOnChainSender',
            toAddress: $publicAddress,
            amount: $receivedAmount,
            confirmations: $confirmations,
            tokenContract: null,
            blockHeight: $latestBlock
        );
    }

    protected function scanTron(CryptoPaymentInvoice $invoice, CryptoNetwork $network, string $publicAddress): void
    {
        $baseUrl = rtrim($network->rpc_endpoint_primary ?: 'https://api.trongrid.io', '/');
        $minTimestampMs = $invoice->created_at ? $invoice->created_at->subMinutes(5)->timestamp * 1000 : 0;
        $contract = trim((string) $invoice->contract_address);

        if ($contract !== '') {
            $resp = Http::timeout(8)->get("{$baseUrl}/v1/accounts/{$publicAddress}/transactions/trc20", [
                'only_to' => 'true',
                'limit' => 20,
                'contract_address' => $contract,
            ]);

            if (!$resp->successful()) {
                return;
            }

            foreach ($resp->json('data', []) as $tx) {
                $ts = (int) ($tx['block_timestamp'] ?? 0);
                if ($ts > 0 && $ts < $minTimestampMs) {
                    continue;
                }
                if (strtolower((string) ($tx['to'] ?? '')) !== strtolower($publicAddress)) {
                    continue;
                }

                $decimals = (int) ($tx['token_info']['decimals'] ?? 6);
                $rawVal = (string) ($tx['value'] ?? '0');
                $amount = bcdiv($rawVal, bcpow('10', (string) $decimals, 0), 8);

                if (bccomp($amount, '0', 8) > 0 && !empty($tx['transaction_id'])) {
                    $this->verificationService->processDetectedTransaction(
                        invoice: $invoice,
                        txHash: (string) $tx['transaction_id'],
                        fromAddress: (string) ($tx['from'] ?? 'TronSender'),
                        toAddress: $publicAddress,
                        amount: $amount,
                        confirmations: max(1, (int) $invoice->required_confirmations),
                        tokenContract: (string) ($tx['token_info']['address'] ?? $contract),
                        blockHeight: null
                    );
                    return;
                }
            }

            return;
        }

        // Native TRX
        $resp = Http::timeout(8)->get("{$baseUrl}/v1/accounts/{$publicAddress}/transactions", [
            'only_to' => 'true',
            'limit' => 20,
        ]);

        if (!$resp->successful()) {
            return;
        }

        foreach ($resp->json('data', []) as $tx) {
            $ts = (int) ($tx['block_timestamp'] ?? 0);
            if ($ts > 0 && $ts < $minTimestampMs) {
                continue;
            }
            $contractCall = $tx['raw_data']['contract'][0] ?? null;
            if (($contractCall['type'] ?? '') !== 'TransferContract') {
                continue;
            }
            $sun = (string) ($contractCall['parameter']['value']['amount'] ?? '0');
            $amount = bcdiv($sun, '1000000', 8);

            if (bccomp($amount, '0', 8) > 0 && !empty($tx['txID'])) {
                $this->verificationService->processDetectedTransaction(
                    invoice: $invoice,
                    txHash: (string) $tx['txID'],
                    fromAddress: 'TronSender',
                    toAddress: $publicAddress,
                    amount: $amount,
                    confirmations: max(1, (int) $invoice->required_confirmations),
                    tokenContract: null,
                    blockHeight: isset($tx['blockNumber']) ? (int) $tx['blockNumber'] : null
                );
                return;
            }
        }
    }

    protected function scanBitcoin(CryptoPaymentInvoice $invoice, CryptoNetwork $network, string $publicAddress): void
    {
        $endpoints = array_filter([
            $network->rpc_endpoint_primary,
            $network->rpc_endpoint_fallback,
            'https://mempool.space/api',
            'https://blockstream.info/api',
        ]);

        $txs = null;
        foreach (array_unique($endpoints) as $baseUrl) {
            try {
                $resp = Http::timeout(8)->get(rtrim($baseUrl, '/') . "/address/{$publicAddress}/txs");
                if ($resp->successful() && is_array($resp->json())) {
                    $txs = $resp->json();
                    break;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        if (empty($txs)) {
            return;
        }

        foreach ($txs as $tx) {
            $sats = 0;
            foreach ($tx['vout'] ?? [] as $vout) {
                if (($vout['scriptpubkey_address'] ?? '') === $publicAddress) {
                    $sats += (int) ($vout['value'] ?? 0);
                }
            }

            if ($sats <= 0 || empty($tx['txid'])) {
                continue;
            }

            $amount = bcdiv((string) $sats, '100000000', 8);
            $confirmed = (bool) ($tx['status']['confirmed'] ?? false);
            $confirmations = $confirmed ? max(1, (int) $invoice->required_confirmations) : 0;

            $this->verificationService->processDetectedTransaction(
                invoice: $invoice,
                txHash: (string) $tx['txid'],
                fromAddress: 'BitcoinSender',
                toAddress: $publicAddress,
                amount: $amount,
                confirmations: $confirmations,
                tokenContract: null,
                blockHeight: isset($tx['status']['block_height']) ? (int) $tx['status']['block_height'] : null
            );
            return;
        }
    }

    protected function getEvmEndpoints(CryptoNetwork $network): array
    {
        $defaultsByChain = match ((int) $network->chain_id) {
            56 => [
                'https://bsc-dataseed.binance.org',
                'https://bsc-dataseed1.defibit.io',
                'https://bsc-rpc.publicnode.com',
            ],
            1 => [
                'https://ethereum-rpc.publicnode.com',
                'https://eth.llamarpc.com',
            ],
            137 => [
                'https://polygon-bor-rpc.publicnode.com',
                'https://polygon-rpc.com',
            ],
            default => [],
        };

        return array_values(array_unique(array_filter([
            $network->rpc_endpoint_primary,
            $network->rpc_endpoint_fallback,
            ...$defaultsByChain,
        ])));
    }

    protected function callEvmRpc(array $endpoints, string $method, array $params = []): mixed
    {
        foreach ($endpoints as $url) {
            try {
                $resp = Http::timeout(6)->post($url, [
                    'jsonrpc' => '2.0',
                    'method' => $method,
                    'params' => $params,
                    'id' => 1,
                ]);

                if ($resp->successful()) {
                    $json = $resp->json();
                    if (is_array($json) && array_key_exists('result', $json) && $json['result'] !== null) {
                        return $json['result'];
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    protected function getEvmLatestBlock(array $endpoints): int
    {
        $res = $this->callEvmRpc($endpoints, 'eth_blockNumber');
        return is_string($res) ? (int) hexdec($res) : 0;
    }

    protected function getEvmNativeBalance(array $endpoints, string $address): ?string
    {
        $res = $this->callEvmRpc($endpoints, 'eth_getBalance', [$address, 'latest']);
        return is_string($res) ? $this->hexToDecString($res) : null;
    }

    protected function getEvmTokenBalance(array $endpoints, string $contract, string $address): ?string
    {
        $padded = str_pad(ltrim(strtolower($address), '0x'), 64, '0', STR_PAD_LEFT);
        $res = $this->callEvmRpc($endpoints, 'eth_call', [
            ['to' => $contract, 'data' => '0x70a08231' . $padded],
            'latest',
        ]);

        return is_string($res) ? $this->hexToDecString($res) : null;
    }

    protected function getEvmTokenDecimals(array $endpoints, string $contract, int $fallback = 18): int
    {
        $res = $this->callEvmRpc($endpoints, 'eth_call', [
            ['to' => $contract, 'data' => '0x313ce567'],
            'latest',
        ]);

        if (is_string($res) && strlen($res) > 2) {
            $dec = (int) hexdec($res);
            if ($dec > 0 && $dec <= 36) {
                return $dec;
            }
        }

        return $fallback;
    }

    protected function findEvmTokenTransferLog(array $endpoints, string $contract, string $address, int $latestBlock): array
    {
        $fromBlock = '0x' . dechex(max(0, $latestBlock - 500));
        $padded = '0x' . str_pad(ltrim(strtolower($address), '0x'), 64, '0', STR_PAD_LEFT);
        $transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';

        $logs = $this->callEvmRpc($endpoints, 'eth_getLogs', [[
            'fromBlock' => $fromBlock,
            'toBlock' => 'latest',
            'address' => $contract,
            'topics' => [$transferTopic, null, $padded],
        ]]);

        if (!is_array($logs) || empty($logs)) {
            return [];
        }

        $latestLog = end($logs);
        $fromTopic = $latestLog['topics'][1] ?? '';
        $fromAddress = strlen($fromTopic) >= 42 ? '0x' . substr($fromTopic, -40) : '0xOnChainSender';

        return [
            'txHash' => $latestLog['transactionHash'] ?? null,
            'blockNumber' => isset($latestLog['blockNumber']) ? (int) hexdec($latestLog['blockNumber']) : null,
            'fromAddress' => $fromAddress,
        ];
    }

    protected function rememberDetectedBlock(CryptoPaymentInvoice $invoice, int $latestBlock): int
    {
        $meta = $invoice->metadata ?? [];
        if (!empty($meta['detected_block'])) {
            return (int) $meta['detected_block'];
        }

        $meta['detected_block'] = $latestBlock;
        $invoice->update(['metadata' => $meta]);

        return $latestBlock;
    }

    protected function resolveOrCreateDetectionRef(CryptoPaymentInvoice $invoice, string $networkKey, int $latestBlock, string $publicAddress): string
    {
        if (!empty($invoice->tx_hash)) {
            return $invoice->tx_hash;
        }

        return 'onchain-' . strtolower($networkKey) . '-' . $latestBlock . '-' . substr(md5($publicAddress), 0, 10);
    }

    protected function hexToDecString(string $hex): string
    {
        $hex = ltrim(strtolower(trim($hex)), '0x');
        if ($hex === '') {
            return '0';
        }

        $dec = '0';
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            $dec = bcadd(bcmul($dec, '16', 0), (string) hexdec($hex[$i]), 0);
        }

        return $dec;
    }
}
