<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\GasReserve;
use App\Models\Wallet\GasSponsorshipTransaction;
use App\Models\Wallet\SigningRequest;
use App\Models\Wallet\TreasuryWallet;
use App\Services\Blockchain\BlockchainAdapterInterface;
use App\Services\Blockchain\BlockchainMonitor;
use App\Services\Deposit\DepositCreditService;
use App\Services\Deposit\DepositDetector;
use App\Services\Deposit\DepositVerifier;
use App\Services\Treasury\ReconciliationService;
use App\Services\Treasury\SweepEngine;
use App\Services\Wallet\HDWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FinalSelfHostedSweepTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_token_sweep_with_gas_sponsorship_signing_request_and_explorer_url(): void
    {
        config([
            'custody.driver' => 'hsm_http',
            'custody.testnet_only' => true,
            'custody.hsm_http.base_url' => 'http://127.0.0.1:8590',
            'custody.hsm_http.hmac_secret' => 'local-test-secret',
            'custody.policies.max_gas_sponsor_per_address_base_units' => '5000000000000000',
        ]);

        Http::fake([
            'http://127.0.0.1:8590/v1/addresses/derive' => Http::response([
                'address' => '0x2222222222222222222222222222222222222222',
            ], 200),
            'http://127.0.0.1:8590/v1/gas/sponsor' => Http::response([
                'signed_tx' => '0x02f8_gas_sponsor',
                'tx_hash' => '0x' . str_repeat('a', 64),
                'request_id' => 'gas-req-001',
            ], 200),
            'http://127.0.0.1:8590/v1/transactions/sign' => Http::response([
                'signed_tx' => '0x02f8_token_sweep',
                'tx_hash' => '0x' . str_repeat('b', 64),
                'request_id' => 'sweep-req-001',
            ], 200),
        ]);

        $sepolia = BlockchainNetwork::create([
            'name' => 'Ethereum Sepolia',
            'network_code' => 'ETH_SEPOLIA',
            'chain_id' => 11155111,
            'network_type' => 'evm',
            'is_testnet' => true,
            'explorer_base_url' => 'https://sepolia.etherscan.io',
            'confirmation_threshold' => 6,
            'is_enabled' => true,
        ]);

        $eth = CryptoAsset::create([
            'network_id' => $sepolia->id,
            'symbol' => 'ETH',
            'name' => 'Sepolia ETH',
            'decimals' => 18,
            'asset_type' => 'native',
            'minimum_deposit' => '1000000000000000',
            'is_enabled' => true,
        ]);

        $usdt = CryptoAsset::create([
            'network_id' => $sepolia->id,
            'symbol' => 'USDT',
            'name' => 'Sepolia USDT',
            'contract_address' => '0x1111111111111111111111111111111111111111',
            'decimals' => 6,
            'asset_type' => 'erc20',
            'minimum_deposit' => '1000000',
            'is_enabled' => true,
        ]);

        TreasuryWallet::create([
            'network_id' => $sepolia->id,
            'wallet_address' => '0xe3Aa47D2f0F4547d900980260121A045B3d6cB39',
            'custody_provider' => 'trust_wallet',
            'is_allowlisted' => true,
            'is_testnet' => true,
            'status' => 'ACTIVE',
        ]);

        GasReserve::create([
            'network_id' => $sepolia->id,
            'asset_id' => $eth->id,
            'available_balance_base_units' => '100000000000000000',
            'minimum_reserve_base_units' => '10000000000000000',
            'status' => 'OPTIMAL',
        ]);

        // Stub BlockchainMonitor adapter to simulate 0 native ETH on deposit address & confirm fee
        $stubAdapter = new class implements BlockchainAdapterInterface {
            public function supportsNetworkType(string $networkType): bool { return true; }
            public function validateAddress(string $address, BlockchainNetwork $network): bool { return (bool) preg_match('/^0x[a-fA-F0-9]{40}$/', $address); }
            public function getLatestBlockHeight(BlockchainNetwork $network): int { return 1000; }
            public function getBlockHash(BlockchainNetwork $network, int $height): ?string { return '0x' . str_repeat('c', 64); }
            public function fetchIncomingTransactions(BlockchainNetwork $network, CryptoAsset $asset, string $address, int $fromHeight = 0): array { return []; }
            public function verifyTransaction(BlockchainNetwork $network, CryptoAsset $asset, string $txHash): array
            {
                return ['exists' => true, 'confirmations' => 6, 'block_height' => 995, 'block_hash' => '0x1', 'from_address' => '0x1', 'to_address' => '0x2', 'contract_address' => null, 'amount_base_units' => '50000000', 'is_reverted' => false];
            }
            public function getAddressBalanceBaseUnits(BlockchainNetwork $network, CryptoAsset $asset, string $address): string { return '0'; }
            public function estimateSweepFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string { return '1300000000000000'; }
            public function broadcastSignedTransaction(BlockchainNetwork $network, string $signedTx): string { return '0x' . str_repeat('b', 64); }
        };

        $this->app->bind(BlockchainMonitor::class, fn () => new class($stubAdapter) extends BlockchainMonitor {
            public function __construct(private BlockchainAdapterInterface $adapter) {}
            public function adapterFor(BlockchainNetwork $network): BlockchainAdapterInterface { return $this->adapter; }
        });

        $user = User::factory()->create();
        $addr = app(HDWalletService::class)->getOrAssignDepositAddress($user, $sepolia, $usdt);
        $deposit = app(DepositDetector::class)->recordObservedDeposit($addr, [
            'tx_hash' => '0x' . str_repeat('9', 64),
            'to_address' => $addr->address,
            'contract_address' => $usdt->contract_address,
            'amount_base_units' => '50000000',
            'confirmations' => 8,
        ]);
        $verified = app(DepositVerifier::class)->verify($deposit, $usdt->contract_address);
        app(DepositCreditService::class)->creditVerifiedDeposit($verified, false);

        $sweep = app(SweepEngine::class)->queueAndExecuteSweep($verified->fresh(), true);
        $this->assertSame(Deposit::STATUS_SWEEPING, $sweep->status);
        $this->assertSame('1300000000000000', $sweep->gas_sponsored_base_units);
        $this->assertSame('https://sepolia.etherscan.io/tx/' . str_repeat('b', 64) ? 'https://sepolia.etherscan.io/tx/0x' . str_repeat('b', 64) : '', $sweep->explorer_url);

        $this->assertSame(1, GasSponsorshipTransaction::where('deposit_id', $deposit->id)->count());
        $this->assertSame(1, SigningRequest::where('intent_type', 'GAS_SPONSOR')->count());
        $this->assertSame(1, SigningRequest::where('intent_type', 'SWEEP')->where('sweep_transaction_id', $sweep->id)->count());

        $confirmed = app(SweepEngine::class)->verifyBroadcastedSweep($sweep);
        $this->assertSame(Deposit::STATUS_SWEPT, $confirmed->status);
        $this->assertTrue(app(ReconciliationService::class)->reconcile()['healthy']);
    }

    public function test_daily_sweep_limit_sends_excess_sweep_to_recovery(): void
    {
        config([
            'custody.driver' => 'mock',
            'custody.testnet_only' => true,
            'custody.policies.max_daily_sweep_base_units' => '10000000', // 10 USDT max per day
        ]);

        $sepolia = BlockchainNetwork::create([
            'name' => 'Ethereum Sepolia',
            'network_code' => 'ETH_SEPOLIA',
            'chain_id' => 11155111,
            'network_type' => 'evm',
            'is_testnet' => true,
            'confirmation_threshold' => 6,
            'is_enabled' => true,
        ]);
        $eth = CryptoAsset::create([
            'network_id' => $sepolia->id,
            'symbol' => 'ETH',
            'name' => 'Sepolia ETH',
            'decimals' => 18,
            'asset_type' => 'native',
            'minimum_deposit' => '1',
            'is_enabled' => true,
        ]);
        TreasuryWallet::create([
            'network_id' => $sepolia->id,
            'wallet_address' => '0xe3Aa47D2f0F4547d900980260121A045B3d6cB39',
            'custody_provider' => 'trust_wallet',
            'is_allowlisted' => true,
            'is_testnet' => true,
            'status' => 'ACTIVE',
        ]);
        GasReserve::create([
            'network_id' => $sepolia->id,
            'asset_id' => $eth->id,
            'available_balance_base_units' => '100000000000000000',
            'minimum_reserve_base_units' => '10000000000000000',
            'status' => 'OPTIMAL',
        ]);

        $this->app->bind(\App\Services\Treasury\GasManager::class, fn () => new class(app(BlockchainMonitor::class)) extends \App\Services\Treasury\GasManager {
            public function estimateRequiredFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string { return '210000'; }
        });

        $user = User::factory()->create();
        $addr = app(HDWalletService::class)->getOrAssignDepositAddress($user, $sepolia, $eth);
        $deposit = app(DepositDetector::class)->recordObservedDeposit($addr, [
            'tx_hash' => '0x' . str_repeat('7', 64),
            'to_address' => $addr->address,
            'amount_base_units' => '50000000', // 50M > 10M daily limit
            'confirmations' => 8,
        ]);
        $verified = app(DepositVerifier::class)->verify($deposit);
        app(DepositCreditService::class)->creditVerifiedDeposit($verified, false);

        $sweep = app(SweepEngine::class)->queueAndExecuteSweep($verified->fresh(), false);
        $this->assertSame(Deposit::STATUS_RECOVERY_REQUIRED, $sweep->status);
        $this->assertSame('DAILY_SWEEP_LIMIT_EXCEEDED', $sweep->last_error_code);
    }
}

