<?php

namespace App\Services\Blockchain;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use RuntimeException;

class XrpAdapter implements BlockchainAdapterInterface
{
    public function __construct(
        protected RpcFailoverService $rpc
    ) {}

    public function supportsNetworkType(string $networkType): bool
    {
        return strtolower($networkType) === 'xrp';
    }

    public function validateAddress(string $address, BlockchainNetwork $network): bool
    {
        return (bool) preg_match('/^r[1-9A-HJ-NP-Za-km-z]{24,34}$/', trim($address));
    }

    public function getLatestBlockHeight(BlockchainNetwork $network): int
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'ledger_current', [[]]);
        return (int) ($res['result']['ledger_current_index'] ?? 0);
    }

    public function getBlockHash(BlockchainNetwork $network, int $height): ?string
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'ledger', [['ledger_index' => $height]]);
        return $res['result']['ledger_hash'] ?? null;
    }

    public function fetchIncomingTransactions(BlockchainNetwork $network, CryptoAsset $asset, string $address, int $fromHeight = 0): array
    {
        return [];
    }

    public function verifyTransaction(BlockchainNetwork $network, CryptoAsset $asset, string $txHash): array
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'tx', [['transaction' => $txHash]]);
        $tx = $res['result'] ?? null;
        if (!is_array($tx) || empty($tx['validated'])) {
            return [
                'exists' => is_array($tx) && !empty($tx['hash']),
                'confirmations' => 0,
                'block_height' => $tx['ledger_index'] ?? null,
                'block_hash' => null,
                'from_address' => $tx['Account'] ?? null,
                'to_address' => $tx['Destination'] ?? null,
                'contract_address' => null,
                'amount_base_units' => is_string($tx['Amount'] ?? null) ? $tx['Amount'] : null,
                'is_reverted' => false,
            ];
        }

        $reverted = ($tx['meta']['TransactionResult'] ?? 'tesSUCCESS') !== 'tesSUCCESS';

        return [
            'exists' => true,
            'confirmations' => 1, // XRP validated ledger is final
            'block_height' => isset($tx['ledger_index']) ? (int) $tx['ledger_index'] : null,
            'block_hash' => null,
            'from_address' => $tx['Account'] ?? null,
            'to_address' => $tx['Destination'] ?? null,
            'contract_address' => null,
            'amount_base_units' => is_string($tx['Amount'] ?? null) ? $tx['Amount'] : null,
            'is_reverted' => $reverted,
        ];
    }

    public function getAddressBalanceBaseUnits(BlockchainNetwork $network, CryptoAsset $asset, string $address): string
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'account_info', [['account' => $address, 'ledger_index' => 'validated']]);
        return (string) ($res['result']['account_data']['Balance'] ?? '0');
    }

    public function estimateSweepFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string
    {
        return '12'; // 12 drops standard XRP fee
    }

    public function broadcastSignedTransaction(BlockchainNetwork $network, string $signedTx): string
    {
        $res = $this->rpc->jsonRpc($this->endpoints($network), 'submit', [['tx_blob' => $signedTx]]);
        $hash = $res['result']['tx_json']['hash'] ?? null;
        if (!$hash) {
            throw new RuntimeException('XRP submit failed.');
        }

        return (string) $hash;
    }

    /**
     * @return array<int, string>
     */
    protected function endpoints(BlockchainNetwork $network): array
    {
        $list = array_values(array_filter(explode(',', (string) $network->rpc_configuration_reference)));
        return !empty($list) ? $list : ['https://s1.ripple.com:51234'];
    }
}

