<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\GasReserve;
use App\Models\Wallet\RecoveryQueueItem;
use App\Models\Wallet\TreasuryWallet;
use App\Services\Blockchain\EvmAdapter;
use App\Services\Blockchain\RpcFailoverService;
use App\Services\Deposit\DepositCreditService;
use App\Services\Deposit\DepositDetector;
use App\Services\Deposit\DepositVerifier;
use App\Services\Security\HsmMpcHttpCustodyProvider;
use App\Services\Security\SigningGateway;
use App\Services\Security\TransactionPolicy;
use App\Services\Treasury\ReconciliationService;
use App\Services\Treasury\SweepEngine;
use App\Services\Treasury\SweepRecoveryService;
use App\Services\Wallet\HDWalletService;
use Database\Seeders\TestnetBlockchainNetworkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class Phase4TestnetIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_testnet_seeder_and_address_generation_across_networks(): void
    {
        config(['custody.driver' => 'mock', 'custody.testnet_only' => true]);
        $this->seed(TestnetBlockchainNetworkSeeder::class);

        $sepolia = BlockchainNetwork::where('network_code', 'ETH_SEPOLIA')->firstOrFail();
        $sepolia->update(['is_enabled' => true]);
        $asset = CryptoAsset::where('network_id', $sepolia->id)->firstOrFail();

        $user = User::factory()->create();
        $address = app(HDWalletService::class)->getOrAssignDepositAddress($user, $sepolia, $asset);

        $this->assertTrue($sepolia->is_testnet);
        $this->assertMatchesRegularExpression('/^0x[a-f0-9]{40}$/i', $address->address);
        $this->assertSame("m/44'/60'/0'/0/0", $address->derivation_path);
    }

    public function test_rpc_failover_and_evm_erc20_incoming_log_detection(): void
    {
        Http::fake([
            'https://dead-sepolia.example.com*' => Http::response(['error' => 'down'], 503),
            'https://healthy-sepolia.example.com*' => function ($request) {
                $body = $request->data();
                $method = $body['method'] ?? '';
                if ($method === 'eth_blockNumber') {
                    return Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => '0x64']);
                }
                if ($method === 'eth_getLogs') {
                    return Http::response([
                        'jsonrpc' => '2.0',
                        'id' => 1,
                        'result' => [[
                            'transactionHash' => '0x' . str_repeat('1', 64),
                            'logIndex' => '0x0',
                            'blockNumber' => '0x5a',
                            'blockHash' => '0x' . str_repeat('2', 64),
                            'topics' => [
                                '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef',
                                '0x000000000000000000000000aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                                '0x000000000000000000000000bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
                            ],
                            'data' => '0x0000000000000000000000000000000000000000000000000000000000989680', // 10,000,000 (10 USDT)
                        ]],
                    ]);
                }

                return Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => null]);
            },
        ]);

        $network = BlockchainNetwork::create([
            'name' => 'Ethereum Sepolia',
            'network_code' => 'ETH_SEPOLIA',
            'chain_id' => 11155111,
            'network_type' => 'evm',
            'is_testnet' => true,
            'rpc_configuration_reference' => 'https://dead-sepolia.example.com,https://healthy-sepolia.example.com',
            'confirmation_threshold' => 6,
            'is_enabled' => true,
        ]);

        $usdt = CryptoAsset::create([
            'network_id' => $network->id,
            'symbol' => 'USDT',
            'name' => 'Sepolia USDT',
            'contract_address' => '0x1111111111111111111111111111111111111111',
            'decimals' => 6,
            'asset_type' => 'erc20',
            'minimum_deposit' => '1000000',
            'is_enabled' => true,
        ]);

        $adapter = app(EvmAdapter::class);
        $txs = $adapter->fetchIncomingTransactions($network, $usdt, '0xbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 80);

        $this->assertCount(1, $txs);
        $this->assertSame('10000000', $txs[0]['amount_base_units']);
        $this->assertSame(11, $txs[0]['confirmations']);
    }

    public function test_hsm_http_custody_provider_signs_hmac_requests_and_rejects_mainnet_in_testnet_mode(): void
    {
        config([
            'custody.driver' => 'hsm_http',
            'custody.testnet_only' => true,
            'custody.hsm_http.base_url' => 'https://custody-sandbox.example.internal',
            'custody.hsm_http.hmac_secret' => 'test-hmac-secret',
            'custody.hsm_http.api_key' => 'sandbox-key',
        ]);

        Http::fake([
            'https://custody-sandbox.example.internal/v1/addresses/derive' => function ($request) {
                $this->assertNotEmpty($request->header('X-Custody-Timestamp')[0] ?? null);
                $this->assertNotEmpty($request->header('X-Custody-Nonce')[0] ?? null);
                $this->assertNotEmpty($request->header('X-Custody-Signature')[0] ?? null);

                return Http::response(['address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e'], 200);
            },
            'https://custody-sandbox.example.internal/v1/transactions/sign' => Http::response([
                'signed_tx' => '0xf86c...',
                'tx_hash' => '0x' . str_repeat('a', 64),
                'request_id' => 'hsm-req-123',
            ], 200),
        ]);

        $testnet = BlockchainNetwork::create([
            'name' => 'Sepolia',
            'network_code' => 'ETH_SEPOLIA',
            'chain_id' => 11155111,
            'network_type' => 'evm',
            'is_testnet' => true,
            'confirmation_threshold' => 6,
            'is_enabled' => true,
        ]);

        $mainnet = BlockchainNetwork::create([
            'name' => 'Ethereum Mainnet',
            'network_code' => 'ETH',
            'chain_id' => 1,
            'network_type' => 'evm',
            'is_testnet' => false,
            'confirmation_threshold' => 12,
            'is_enabled' => false,
        ]);

        $treasury = TreasuryWallet::create([
            'network_id' => $testnet->id,
            'wallet_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'custody_provider' => 'hsm_http',
            'is_allowlisted' => true,
            'is_testnet' => true,
            'status' => 'ACTIVE',
        ]);

        $gateway = app(SigningGateway::class);
        $addr = $gateway->deriveDepositAddress($testnet, "m/44'/60'/0'/0/0", 0);
        $this->assertSame('0x742d35Cc6634C0532925a3b844Bc454e4438f44e', $addr);

        $signed = $gateway->signTreasurySweep($testnet, $treasury->wallet_address, '1000000', []);
        $this->assertSame('hsm-req-123', $signed['custody_request_id']);

        // Mainnet sweep blocked while CUSTODY_TESTNET_ONLY=true
        $this->expectException(RuntimeException::class);
        $gateway->signTreasurySweep($mainnet, $treasury->wallet_address, '1000000', []);
    }

    public function test_testnet_mainnet_treasury_mismatch_is_blocked(): void
    {
        config(['custody.driver' => 'mock', 'custody.testnet_only' => false]);

        $testnet = BlockchainNetwork::create([
            'name' => 'Sepolia',
            'network_code' => 'ETH_SEPOLIA',
            'chain_id' => 11155111,
            'network_type' => 'evm',
            'is_testnet' => true,
            'confirmation_threshold' => 6,
            'is_enabled' => true,
        ]);

        // Treasury marked as mainnet (is_testnet=false) attached to testnet network
        $mainnetTreasury = TreasuryWallet::create([
            'network_id' => $testnet->id,
            'wallet_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'custody_provider' => 'trust_wallet',
            'is_allowlisted' => true,
            'is_testnet' => false,
            'status' => 'ACTIVE',
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(SigningGateway::class)->signTreasurySweep($testnet, $mainnetTreasury->wallet_address, '1000000', []);
    }

    public function test_sweep_recovery_service_resolves_failed_sweep_once_gas_is_replenished(): void
    {
        config(['custody.driver' => 'mock', 'custody.testnet_only' => true]);

        $network = BlockchainNetwork::create([
            'name' => 'Sepolia',
            'network_code' => 'ETH_SEPOLIA',
            'chain_id' => 11155111,
            'network_type' => 'evm',
            'is_testnet' => true,
            'confirmation_threshold' => 6,
            'is_enabled' => true,
        ]);
        $eth = CryptoAsset::create([
            'network_id' => $network->id,
            'symbol' => 'ETH',
            'name' => 'Sepolia ETH',
            'decimals' => 18,
            'asset_type' => 'native',
            'minimum_deposit' => '1000000000000000',
            'is_enabled' => true,
        ]);
        TreasuryWallet::create([
            'network_id' => $network->id,
            'wallet_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'custody_provider' => 'trust_wallet',
            'is_allowlisted' => true,
            'is_testnet' => true,
            'status' => 'ACTIVE',
        ]);
        $reserve = GasReserve::create([
            'network_id' => $network->id,
            'asset_id' => $eth->id,
            'available_balance_base_units' => '0',
            'minimum_reserve_base_units' => '10000000000000000',
            'status' => 'LOW',
        ]);

        $user = User::factory()->create();
        $addr = app(HDWalletService::class)->getOrAssignDepositAddress($user, $network, $eth);
        $deposit = app(DepositDetector::class)->recordObservedDeposit($addr, [
            'tx_hash' => '0x' . str_repeat('9', 64),
            'to_address' => $addr->address,
            'amount_base_units' => '50000000000000000',
            'confirmations' => 8,
        ]);
        $verified = app(DepositVerifier::class)->verify($deposit);
        app(DepositCreditService::class)->creditVerifiedDeposit($verified, false);

        // Stub gas estimation so test does not call external RPC
        $this->app->bind(\App\Services\Treasury\GasManager::class, function () {
            return new class(app(\App\Services\Blockchain\BlockchainMonitor::class)) extends \App\Services\Treasury\GasManager {
                public function estimateRequiredFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string
                {
                    return '210000000000000';
                }
            };
        });

        $sweep = app(SweepEngine::class)->queueAndExecuteSweep($verified->fresh(), false);
        $this->assertSame(Deposit::STATUS_RECOVERY_REQUIRED, $sweep->status);

        // Replenish gas reserve and run recovery
        $reserve->update(['available_balance_base_units' => '100000000000000000', 'status' => 'OPTIMAL']);
        $item = RecoveryQueueItem::where('operation_type', 'SWEEP')->where('reference_id', $sweep->id)->firstOrFail();

        $resolved = app(SweepRecoveryService::class)->processItem($item);
        $this->assertTrue($resolved);
        $this->assertSame('RESOLVED', $item->fresh()->status);
        $this->assertSame(Deposit::STATUS_SWEEPING, $sweep->fresh()->status);
    }
}

