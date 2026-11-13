<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\GasReserve;
use App\Models\Wallet\LedgerEntry;
use App\Models\Wallet\RecoveryQueueItem;
use App\Models\Wallet\TreasuryWallet;
use App\Services\Deposit\DepositCreditService;
use App\Services\Deposit\DepositDetector;
use App\Services\Deposit\DepositReorganizationHandler;
use App\Services\Deposit\DepositVerifier;
use App\Services\Security\SigningGateway;
use App\Services\Security\TransactionPolicy;
use App\Services\Treasury\ReconciliationService;
use App\Services\Treasury\SweepEngine;
use App\Services\Wallet\HDWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class EnterpriseHdWalletTreasuryTest extends TestCase
{
    use RefreshDatabase;

    protected BlockchainNetwork $ethNetwork;
    protected CryptoAsset $ethAsset;
    protected CryptoAsset $usdtAsset;
    protected TreasuryWallet $treasuryWallet;

    protected function setUp(): void
    {
        parent::setUp();

        config(['custody.driver' => 'mock']);

        $this->ethNetwork = BlockchainNetwork::create([
            'name' => 'Ethereum',
            'network_code' => 'ETH',
            'chain_id' => 1,
            'network_type' => 'evm',
            'is_testnet' => true,
            'rpc_configuration_reference' => 'https://ethereum-rpc.publicnode.com',
            'confirmation_threshold' => 12,
            'is_enabled' => true,
        ]);

        $this->ethAsset = CryptoAsset::create([
            'network_id' => $this->ethNetwork->id,
            'symbol' => 'ETH',
            'name' => 'Ethereum',
            'contract_address' => null,
            'decimals' => 18,
            'asset_type' => 'native',
            'minimum_deposit' => '1000000000000000',
            'is_enabled' => true,
        ]);

        $this->usdtAsset = CryptoAsset::create([
            'network_id' => $this->ethNetwork->id,
            'symbol' => 'USDT',
            'name' => 'Tether USD (ERC20)',
            'contract_address' => '0xdac17f958d2ee523a2206206994597c13d831ec7',
            'decimals' => 6,
            'asset_type' => 'erc20',
            'minimum_deposit' => '1000000',
            'is_enabled' => true,
        ]);

        $this->treasuryWallet = TreasuryWallet::create([
            'network_id' => $this->ethNetwork->id,
            'wallet_address' => '0xe3Aa47D2f0F4547d900980260121A045B3d6cB39',
            'custody_provider' => 'trust_wallet',
            'is_allowlisted' => true,
            'is_testnet' => true,
            'status' => 'ACTIVE',
        ]);

        GasReserve::create([
            'network_id' => $this->ethNetwork->id,
            'asset_id' => $this->ethAsset->id,
            'treasury_wallet_id' => $this->treasuryWallet->id,
            'available_balance_base_units' => '1000000000000000000', // 1 ETH
            'minimum_reserve_base_units' => '100000000000000000',
            'status' => 'OPTIMAL',
        ]);
    }

    public function test_idempotent_unique_hd_address_allocation_never_reuses_another_users_address(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $hd = app(HDWalletService::class);

        $addr1a = $hd->getOrAssignDepositAddress($user1, $this->ethNetwork, $this->usdtAsset);
        $addr1b = $hd->getOrAssignDepositAddress($user1, $this->ethNetwork, $this->usdtAsset);
        $addr2 = $hd->getOrAssignDepositAddress($user2, $this->ethNetwork, $this->usdtAsset);

        $this->assertSame($addr1a->id, $addr1b->id);
        $this->assertSame($addr1a->address, $addr1b->address);
        $this->assertNotSame($addr1a->address, $addr2->address);
        $this->assertNotSame($addr1a->derivation_index, $addr2->derivation_index);
    }

    public function test_end_to_end_deposit_verification_credit_sweep_and_reconciliation(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $hd = app(HDWalletService::class);
        $detector = app(DepositDetector::class);
        $verifier = app(DepositVerifier::class);
        $creditService = app(DepositCreditService::class);
        $reconciler = app(ReconciliationService::class);

        $addr = $hd->getOrAssignDepositAddress($user, $this->ethNetwork, $this->usdtAsset);

        $deposit = $detector->recordObservedDeposit($addr, [
            'tx_hash' => '0x' . str_repeat('a', 64),
            'from_address' => '0x1111111111111111111111111111111111111111',
            'to_address' => $addr->address,
            'contract_address' => $this->usdtAsset->contract_address,
            'amount_base_units' => '50000000', // 50 USDT
            'confirmations' => 15,
            'block_height' => 21000000,
            'block_hash' => '0x' . str_repeat('b', 64),
        ]);

        $verified = $verifier->verify($deposit, $this->usdtAsset->contract_address);
        $this->assertSame(Deposit::STATUS_VERIFIED, $verified->status);

        // Credit twice to confirm idempotency
        $ledger1 = $creditService->creditVerifiedDeposit($verified, false);
        $ledger2 = $creditService->creditVerifiedDeposit($verified->fresh(), false);
        $this->assertSame($ledger1->id, $ledger2->id);
        $this->assertSame(1, LedgerEntry::where('deposit_id', $deposit->id)->count());

        // Mock SweepEngine fee lookup without live RPC
        $sweepEngine = $this->getMockBuilder(SweepEngine::class)
            ->setConstructorArgs([
                app(\App\Services\Treasury\TreasuryService::class),
                $this->createStubGasManager('210000000000000'),
                app(SigningGateway::class),
                app(\App\Services\Blockchain\BlockchainMonitor::class),
            ])
            ->onlyMethods([])
            ->getMock();

        $sweep = $sweepEngine->queueAndExecuteSweep($verified->fresh(), false);
        $this->assertSame(Deposit::STATUS_SWEEPING, $sweep->status);
        $this->assertNotEmpty($sweep->transaction_hash);

        $confirmedSweep = $sweepEngine->verifyBroadcastedSweep($sweep, true);
        $this->assertSame(Deposit::STATUS_SWEPT, $confirmedSweep->status);

        $recon = $reconciler->reconcile();
        $this->assertTrue($recon['healthy']);
        $this->assertSame('50000000', $recon['summary']['total_credited_base_units']);
    }

    public function test_token_contract_mismatch_and_insufficient_confirmations_are_rejected(): void
    {
        $user = User::factory()->create();
        $addr = app(HDWalletService::class)->getOrAssignDepositAddress($user, $this->ethNetwork, $this->usdtAsset);
        $detector = app(DepositDetector::class);
        $verifier = app(DepositVerifier::class);

        $deposit = $detector->recordObservedDeposit($addr, [
            'tx_hash' => '0x' . str_repeat('c', 64),
            'to_address' => $addr->address,
            'amount_base_units' => '25000000',
            'confirmations' => 2,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $verifier->verify($deposit, '0x0000000000000000000000000000000000000000');
    }

    public function test_insufficient_gas_routes_sweep_to_recovery_queue_without_losing_user_credit(): void
    {
        $user = User::factory()->create();
        $addr = app(HDWalletService::class)->getOrAssignDepositAddress($user, $this->ethNetwork, $this->usdtAsset);
        $deposit = app(DepositDetector::class)->recordObservedDeposit($addr, [
            'tx_hash' => '0x' . str_repeat('d', 64),
            'to_address' => $addr->address,
            'amount_base_units' => '25000000',
            'confirmations' => 15,
        ]);
        $verified = app(DepositVerifier::class)->verify($deposit, $this->usdtAsset->contract_address);
        app(DepositCreditService::class)->creditVerifiedDeposit($verified, false);

        // Drain gas reserve
        GasReserve::where('network_id', $this->ethNetwork->id)->update(['available_balance_base_units' => '0']);

        $sweepEngine = new SweepEngine(
            app(\App\Services\Treasury\TreasuryService::class),
            $this->createStubGasManager('500000000000000', false),
            app(SigningGateway::class),
            app(\App\Services\Blockchain\BlockchainMonitor::class)
        );

        $sweep = $sweepEngine->queueAndExecuteSweep($verified->fresh(), false);
        $this->assertSame(Deposit::STATUS_RECOVERY_REQUIRED, $sweep->status);
        $this->assertSame('INSUFFICIENT_GAS_RESERVE', $sweep->last_error_code);
        $this->assertNotNull($verified->fresh()->credited_at);
        $this->assertSame(1, RecoveryQueueItem::where('operation_type', 'SWEEP')->where('reference_id', $sweep->id)->count());
    }

    public function test_reorg_handling_flags_deposit_and_alerts_reconciliation(): void
    {
        $user = User::factory()->create();
        $addr = app(HDWalletService::class)->getOrAssignDepositAddress($user, $this->ethNetwork, $this->usdtAsset);
        $deposit = app(DepositDetector::class)->recordObservedDeposit($addr, [
            'tx_hash' => '0x' . str_repeat('e', 64),
            'to_address' => $addr->address,
            'amount_base_units' => '15000000',
            'confirmations' => 3,
            'block_height' => 21000005,
            'block_hash' => '0x' . str_repeat('f', 64),
        ]);

        app(DepositReorganizationHandler::class)->handleReorg($deposit->blockchainTransaction);

        $this->assertSame(Deposit::STATUS_REORGED, $deposit->fresh()->status);
        $recon = app(ReconciliationService::class)->reconcile();
        $this->assertFalse($recon['healthy']);
        $this->assertSame('REORGED_TRANSACTION', $recon['alerts'][0]['code']);
    }

    public function test_security_policies_block_non_allowlisted_destinations_emergency_pause_and_unauthorized_apis(): void
    {
        $gateway = app(SigningGateway::class);
        $policy = app(TransactionPolicy::class);

        // Non-allowlisted destination rejected
        try {
            $gateway->signTreasurySweep(
                $this->ethNetwork,
                '0x9999999999999999999999999999999999999999',
                '1000000',
                []
            );
            $this->fail('Expected non-allowlisted destination to throw.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('not an active allowlisted treasury', $e->getMessage());
        }

        // Emergency outbound pause blocks signing
        $policy->pauseOutbound();
        try {
            $gateway->signTreasurySweep(
                $this->ethNetwork,
                $this->treasuryWallet->wallet_address,
                '1000000',
                []
            );
            $this->fail('Expected emergency pause to block signing.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('paused', $e->getMessage());
        }
        $policy->resumeOutbound();

        // Unauthenticated check first (before actingAs)
        $this->getJson('/api/v1/wallet/overview')->assertStatus(401);

        // API privilege escalation check
        $regularUser = User::factory()->create();
        $this->actingAs($regularUser)
            ->getJson('/api/v1/admin/treasury/overview')
            ->assertStatus(403);

        $this->actingAs($regularUser)
            ->postJson('/api/v1/wallet/deposit/address', [
                'network_code' => 'ETH',
                'asset_symbol' => 'USDT',
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['address', 'network_code', 'asset_symbol', 'derivation_path']);
    }

    protected function createStubGasManager(string $fee, bool $hasReserve = true): \App\Services\Treasury\GasManager
    {
        return new class($fee, $hasReserve) extends \App\Services\Treasury\GasManager {
            public function __construct(private string $fee, private bool $hasReserve) {}

            public function estimateRequiredFeeBaseUnits(BlockchainNetwork $network, CryptoAsset $asset): string
            {
                return $this->fee;
            }

            public function hasSufficientGasReserve(BlockchainNetwork $network, string $requiredFeeBaseUnits): bool
            {
                return $this->hasReserve;
            }
        };
    }
}
