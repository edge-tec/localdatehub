<?php

namespace Tests\Unit;

use App\Models\Wallet\BlockchainNetwork;
use App\Services\Blockchain\BitcoinAdapter;
use App\Services\Blockchain\EvmAdapter;
use App\Services\Blockchain\SolanaAdapter;
use App\Services\Blockchain\TronAdapter;
use App\Services\Blockchain\XrpAdapter;
use App\Services\Security\BitGoCustodyAdapter;
use App\Services\Security\FireblocksCustodyAdapter;
use App\Services\Security\FordefiCustodyAdapter;
use App\Services\Support\BaseUnitMath;
use App\Services\Wallet\DerivationPathResolver;
use RuntimeException;
use Tests\TestCase;

class EnterpriseHdWalletUnitTest extends TestCase
{
    public function test_network_specific_derivation_paths_are_enforced(): void
    {
        $resolver = new DerivationPathResolver();

        $btc = new BlockchainNetwork(['network_code' => 'BTC', 'network_type' => 'utxo']);
        $eth = new BlockchainNetwork(['network_code' => 'ETH', 'network_type' => 'evm']);
        $tron = new BlockchainNetwork(['network_code' => 'TRON', 'network_type' => 'tron']);
        $sol = new BlockchainNetwork(['network_code' => 'SOL', 'network_type' => 'solana']);
        $ltc = new BlockchainNetwork(['network_code' => 'LTC', 'network_type' => 'utxo']);
        $doge = new BlockchainNetwork(['network_code' => 'DOGE', 'network_type' => 'utxo']);
        $xrp = new BlockchainNetwork(['network_code' => 'XRP', 'network_type' => 'xrp']);

        $this->assertSame("m/84'/0'/0'/0/5", $resolver->resolve($btc, 5));
        $this->assertSame("m/44'/60'/0'/0/5", $resolver->resolve($eth, 5));
        $this->assertSame("m/44'/195'/0'/0/5", $resolver->resolve($tron, 5));
        $this->assertSame("m/44'/501'/5'/0'", $resolver->resolve($sol, 5));
        $this->assertSame("m/84'/2'/0'/0/5", $resolver->resolve($ltc, 5));
        $this->assertSame("m/44'/3'/0'/0/5", $resolver->resolve($doge, 5));
        $this->assertSame("m/44'/144'/0'/0/5", $resolver->resolve($xrp, 5));
    }

    public function test_network_adapters_validate_addresses_strictly(): void
    {
        $btcNet = new BlockchainNetwork(['network_code' => 'BTC', 'network_type' => 'utxo']);
        $ethNet = new BlockchainNetwork(['network_code' => 'ETH', 'network_type' => 'evm']);
        $tronNet = new BlockchainNetwork(['network_code' => 'TRON', 'network_type' => 'tron']);
        $solNet = new BlockchainNetwork(['network_code' => 'SOL', 'network_type' => 'solana']);
        $xrpNet = new BlockchainNetwork(['network_code' => 'XRP', 'network_type' => 'xrp']);

        $btc = app(BitcoinAdapter::class);
        $evm = app(EvmAdapter::class);
        $tron = app(TronAdapter::class);
        $sol = app(SolanaAdapter::class);
        $xrp = app(XrpAdapter::class);

        $this->assertTrue($btc->validateAddress('bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh', $btcNet));
        $this->assertFalse($btc->validateAddress('0x742d35Cc6634C0532925a3b844Bc454e4438f44e', $btcNet));

        $this->assertTrue($evm->validateAddress('0x742d35Cc6634C0532925a3b844Bc454e4438f44e', $ethNet));
        $this->assertFalse($evm->validateAddress('TEpqggfDUnVw4i12cM2RYEgwhgaruhAmKo', $ethNet));

        $this->assertTrue($tron->validateAddress('TEpqggfDUnVw4i12cM2RYEgwhgaruhAmKo', $tronNet));
        $this->assertTrue($sol->validateAddress('11111111111111111111111111111111', $solNet));
        $this->assertTrue($xrp->validateAddress('rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe', $xrpNet));
    }

    public function test_base_unit_integer_math_avoids_floating_point_errors(): void
    {
        $a = '1000000000000000000'; // 1 ETH in wei
        $b = '250000000000000000';  // 0.25 ETH in wei

        $this->assertSame('1250000000000000000', BaseUnitMath::add($a, $b));
        $this->assertSame('750000000000000000', BaseUnitMath::sub($a, $b));
        $this->assertSame(1, BaseUnitMath::comp($a, $b));
    }

    public function test_vendor_custody_adapters_fail_safely_when_unconfigured(): void
    {
        $net = new BlockchainNetwork(['network_code' => 'ETH', 'network_type' => 'evm']);

        foreach ([FireblocksCustodyAdapter::class, BitGoCustodyAdapter::class, FordefiCustodyAdapter::class] as $cls) {
            $adapter = app($cls);
            $health = $adapter->healthCheck();
            $this->assertFalse($health['ok']);

            try {
                $adapter->derivePublicAddress($net, "m/44'/60'/0'/0/0", 0);
                $this->fail("Expected RuntimeException for unconfigured {$cls}");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('not configured', $e->getMessage());
            }
        }
    }
}

