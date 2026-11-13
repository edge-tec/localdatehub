<?php

namespace App\Services\Deposit;

use App\Models\Wallet\Deposit;
use App\Services\Support\BaseUnitMath;
use InvalidArgumentException;

class DepositVerifier
{
    /**
     * Enforce all 12 mandatory deposit verification checks before crediting.
     */
    public function verify(
        Deposit $deposit,
        ?string $observedContractAddress = null,
        bool $isReverted = false
    ): Deposit {
        $deposit->loadMissing(['blockchainTransaction', 'walletAddress', 'network', 'asset']);
        $btx = $deposit->blockchainTransaction;
        $addr = $deposit->walletAddress;
        $network = $deposit->network;
        $asset = $deposit->asset;

        if (!$btx || !$addr || !$network || !$asset) {
            throw new InvalidArgumentException('Deposit is missing required blockchain, address, network, or asset relations.');
        }

        // 1 & 2: Transaction exists & hash is non-empty
        if (trim($btx->transaction_hash) === '') {
            throw new InvalidArgumentException('Invalid empty transaction hash.');
        }

        // 3: Destination address matches assigned deposit address
        if (strtolower(trim($btx->recipient_address)) !== strtolower(trim($addr->address))) {
            throw new InvalidArgumentException('Destination address mismatch.');
        }

        // 4: Network matches assigned deposit address
        if ($btx->network_id !== $addr->network_id || $deposit->network_id !== $addr->network_id) {
            throw new InvalidArgumentException('Blockchain network mismatch.');
        }

        // 5: Token contract/mint matches asset if non-native
        if (!empty($asset->contract_address)) {
            if (empty($observedContractAddress) || strtolower(trim($observedContractAddress)) !== strtolower(trim($asset->contract_address))) {
                throw new InvalidArgumentException('Token contract address mismatch.');
            }
        }

        // 6 & 12: Positive valid integer base-unit amount >= minimum deposit
        $amount = BaseUnitMath::normalize($btx->amount_base_units);
        if (!BaseUnitMath::isPositive($amount)) {
            throw new InvalidArgumentException('Deposit amount must be positive.');
        }
        if (!empty($asset->minimum_deposit) && BaseUnitMath::comp($amount, (string) $asset->minimum_deposit) < 0) {
            throw new InvalidArgumentException('Deposit amount below minimum deposit threshold.');
        }

        // 7 & 11: Not already credited
        if ($deposit->credited_at !== null || in_array($deposit->status, [
            Deposit::STATUS_CREDITED,
            Deposit::STATUS_SWEEP_QUEUED,
            Deposit::STATUS_SWEEPING,
            Deposit::STATUS_SWEPT,
        ], true)) {
            return $deposit;
        }

        // 8: Sufficient blockchain confirmations
        if ($btx->confirmation_count < $network->confirmation_threshold) {
            $deposit->update(['status' => Deposit::STATUS_CONFIRMING]);
            throw new InvalidArgumentException(
                "Insufficient confirmations ({$btx->confirmation_count}/{$network->confirmation_threshold})."
            );
        }

        // 9: Not reverted / invalidated
        if ($isReverted || $btx->transaction_status === Deposit::STATUS_FAILED || $btx->transaction_status === Deposit::STATUS_REORGED) {
            $deposit->update(['status' => Deposit::STATUS_FAILED]);
            throw new InvalidArgumentException('Transaction reverted or invalidated on-chain.');
        }

        // 10: Mapped to correct user
        if ($deposit->user_id !== $addr->user_id) {
            throw new InvalidArgumentException('Deposit user mapping mismatch.');
        }

        $btx->update([
            'transaction_status' => Deposit::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);

        $deposit->update(['status' => Deposit::STATUS_VERIFIED]);

        return $deposit->fresh();
    }
}

