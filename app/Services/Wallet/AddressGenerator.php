<?php

namespace App\Services\Wallet;

use App\Models\Wallet\BlockchainNetwork;
use App\Services\Blockchain\BlockchainMonitor;
use App\Services\Security\SigningGateway;
use RuntimeException;

class AddressGenerator
{
    public function __construct(
        protected SigningGateway $signingGateway,
        protected DerivationPathResolver $pathResolver,
        protected BlockchainMonitor $monitor
    ) {}

    /**
     * @return array{address: string, derivation_path: string, address_type: string}
     */
    public function generate(BlockchainNetwork $network, int $index): array
    {
        $path = $this->pathResolver->resolve($network, $index);
        $address = trim($this->signingGateway->deriveDepositAddress($network, $path, $index));

        $adapter = $this->monitor->adapterFor($network);
        if (!$adapter->validateAddress($address, $network)) {
            throw new RuntimeException(
                "Generated address [{$address}] failed network format validation for [{$network->network_code}]."
            );
        }

        return [
            'address' => $address,
            'derivation_path' => $path,
            'address_type' => $this->pathResolver->addressType($network),
        ];
    }
}

