<?php

namespace App\Services\Wallet;

use App\Models\Wallet\BlockchainNetwork;
use InvalidArgumentException;

class DerivationPathResolver
{
    public function resolve(BlockchainNetwork $network, int $index): string
    {
        if ($index < 0) {
            throw new InvalidArgumentException("Derivation index must be non-negative, got [{$index}].");
        }

        $code = strtoupper($network->network_code);
        $type = strtolower($network->network_type);

        return match (true) {
            $code === 'BTC' => "m/84'/0'/0'/0/{$index}",
            $code === 'LTC' => "m/84'/2'/0'/0/{$index}",
            $code === 'DOGE' => "m/44'/3'/0'/0/{$index}",
            $type === 'evm' => "m/44'/60'/0'/0/{$index}",
            $type === 'tron' => "m/44'/195'/0'/0/{$index}",
            $type === 'solana' => "m/44'/501'/{$index}'/0'",
            $type === 'xrp' => "m/44'/144'/0'/0/{$index}",
            default => throw new InvalidArgumentException("Unsupported network derivation standard for [{$network->network_code}]."),
        };
    }

    public function addressType(BlockchainNetwork $network): string
    {
        $code = strtoupper($network->network_code);
        $type = strtolower($network->network_type);

        return match (true) {
            in_array($code, ['BTC', 'LTC'], true) => 'p2wpkh',
            $code === 'DOGE' => 'p2pkh',
            $type === 'evm' => 'evm',
            $type === 'tron' => 'trc20',
            $type === 'solana' => 'spl',
            $type === 'xrp' => 'xrp_classic',
            default => 'deposit',
        };
    }
}

