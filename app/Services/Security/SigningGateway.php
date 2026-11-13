<?php

namespace App\Services\Security;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\WalletAuditLog;
use Illuminate\Support\Facades\App;
use RuntimeException;

class SigningGateway
{
    public function __construct(
        protected TransactionPolicy $policy,
        protected TreasuryAddressValidator $addressValidator
    ) {}

    public function provider(): CustodyProviderInterface
    {
        $driver = (string) config('custody.driver', 'hsm_http');

        if ($driver === 'mock' && App::isProduction()) {
            throw new RuntimeException('Mock custody provider is strictly forbidden in production.');
        }

        return match ($driver) {
            'mock' => app(MockCustodyProvider::class),
            'fireblocks' => app(FireblocksCustodyAdapter::class),
            'bitgo' => app(BitGoCustodyAdapter::class),
            'fordefi' => app(FordefiCustodyAdapter::class),
            default => app(HsmMpcHttpCustodyProvider::class),
        };
    }

    public function deriveDepositAddress(
        BlockchainNetwork $network,
        string $derivationPath,
        int $index
    ): string {
        return $this->provider()->derivePublicAddress($network, $derivationPath, $index);
    }

    /**
     * Authorize and sign an outbound sweep transaction through HSM/MPC.
     *
     * @param array<string, mixed> $signingPayload
     * @return array{signed_tx: string, tx_hash: string|null, custody_request_id: string}
     */
    public function signTreasurySweep(
        BlockchainNetwork $network,
        string $destinationAddress,
        string $amountBaseUnits,
        array $signingPayload,
        ?int $actorId = null
    ): array {
        $this->policy->assertOutboundAllowed($network, $amountBaseUnits);
        $treasury = $this->addressValidator->validateAllowlistedTreasury($network, $destinationAddress);

        $result = $this->provider()->signTransaction(array_merge($signingPayload, [
            'network_code' => $network->network_code,
            'network_type' => $network->network_type,
            'to_address' => $treasury->wallet_address,
            'amount_base_units' => $amountBaseUnits,
        ]));

        WalletAuditLog::create([
            'actor_id' => $actorId,
            'action' => 'TREASURY_SWEEP_SIGNED',
            'resource_type' => 'treasury_wallet',
            'resource_id' => (string) $treasury->id,
            'metadata' => [
                'network_code' => $network->network_code,
                'amount_base_units' => $amountBaseUnits,
                'custody_provider' => $this->provider()->providerKey(),
                'custody_request_id' => $result['custody_request_id'],
            ],
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);

        return $result;
    }
}

