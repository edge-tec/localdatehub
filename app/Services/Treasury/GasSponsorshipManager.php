<?php

namespace App\Services\Treasury;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\GasSponsorshipTransaction;
use App\Models\Wallet\SigningRequest;
use App\Models\Wallet\WalletAuditLog;
use App\Services\Blockchain\BlockchainMonitor;
use App\Services\Security\SigningGateway;
use App\Services\Security\TransactionPolicy;
use App\Services\Support\BaseUnitMath;
use Illuminate\Support\Str;
use RuntimeException;

class GasSponsorshipManager
{
    public function __construct(
        protected GasManager $gasManager,
        protected SigningGateway $signingGateway,
        protected BlockchainMonitor $monitor,
        protected TransactionPolicy $policy
    ) {}

    /**
     * Ensure a deposit address has sufficient native gas to sweep tokens.
     * If insufficient, sponsor native gas within policy limits and return sponsorship TX.
     */
    public function ensureGasForTokenSweep(
        Deposit $deposit,
        string $requiredFeeBaseUnits,
        bool $broadcastLive = true
    ): ?GasSponsorshipTransaction {
        $deposit->loadMissing(['network', 'walletAddress']);
        $network = $deposit->network;
        $address = $deposit->walletAddress;

        if (!$network || !$address) {
            return null;
        }

        $nativeAsset = CryptoAsset::where('network_id', $network->id)
            ->where('asset_type', 'native')
            ->first();

        if (!$nativeAsset) {
            return null;
        }

        $adapter = $this->monitor->adapterFor($network);
        $currentNative = $adapter->getAddressBalanceBaseUnits($network, $nativeAsset, $address->address);

        if (BaseUnitMath::comp($currentNative, $requiredFeeBaseUnits) >= 0) {
            return null; // Deposit address already has enough gas
        }

        $deficit = BaseUnitMath::sub($requiredFeeBaseUnits, $currentNative);
        $maxSponsor = (string) config('custody.policies.max_gas_sponsor_per_address_base_units', '5000000000000000');

        if (BaseUnitMath::comp($deficit, $maxSponsor) > 0) {
            throw new RuntimeException(
                "Required gas sponsorship [{$deficit}] exceeds per-address limit [{$maxSponsor}] on [{$network->network_code}]."
            );
        }

        if ($this->policy->isOutboundPaused()) {
            throw new RuntimeException('Gas sponsorship blocked while outbound pause is active.');
        }

        if ((bool) config('custody.testnet_only', true) && !$network->is_testnet) {
            throw new RuntimeException('Mainnet gas sponsorship blocked in testnet-only mode.');
        }

        if (!$this->gasManager->hasSufficientGasReserve($network, $deficit)) {
            throw new RuntimeException('Insufficient gas reserve for sponsorship.');
        }

        $provider = $this->signingGateway->provider();
        if (!method_exists($provider, 'sponsorGas')) {
            throw new RuntimeException("Custody provider [{$provider->providerKey()}] does not support gas sponsorship.");
        }

        $signed = $provider->sponsorGas([
            'network_code' => $network->network_code,
            'chain_id' => $network->chain_id,
            'target_address' => $address->address,
            'amount_base_units' => $deficit,
        ]);

        $txHash = $signed['tx_hash'];
        if ($broadcastLive && empty($txHash) && !empty($signed['signed_tx'])) {
            $txHash = $adapter->broadcastSignedTransaction($network, $signed['signed_tx']);
        }

        SigningRequest::create([
            'network_id' => $network->id,
            'request_uuid' => (string) ($signed['custody_request_id'] ?? Str::uuid()),
            'intent_type' => 'GAS_SPONSOR',
            'from_address' => 'gas_sponsor_wallet',
            'to_address' => $address->address,
            'amount_base_units' => $deficit,
            'custody_provider' => $provider->providerKey(),
            'status' => 'SIGNED',
        ]);

        $this->gasManager->recordGasConsumption($network, $deficit);

        $sponsorTx = GasSponsorshipTransaction::create([
            'network_id' => $network->id,
            'deposit_id' => $deposit->id,
            'target_address' => $address->address,
            'amount_base_units' => $deficit,
            'transaction_hash' => $txHash,
            'status' => 'BROADCAST',
        ]);

        WalletAuditLog::create([
            'action' => 'GAS_SPONSORSHIP_EXECUTED',
            'resource_type' => 'deposit',
            'resource_id' => (string) $deposit->id,
            'metadata' => [
                'target_address' => $address->address,
                'amount_base_units' => $deficit,
                'tx_hash' => $txHash,
            ],
            'created_at' => now(),
        ]);

        return $sponsorTx;
    }
}

