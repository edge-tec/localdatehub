<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\CryptoCheckoutOrder;
use App\Services\Checkout\CryptoCheckoutService;
use App\Services\Deposit\DepositCreditService;
use App\Services\Deposit\DepositDetector;
use App\Services\Deposit\DepositVerifier;
use Database\Seeders\TestnetBlockchainNetworkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CryptoCheckoutOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('custody.driver', 'mock');
        $this->seed(TestnetBlockchainNetworkSeeder::class);
        BlockchainNetwork::where('network_code', 'ETH_SEPOLIA')->update(['is_enabled' => true]);
    }

    public function test_user_can_create_checkout_order_and_isolation_is_enforced(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $res = $this->actingAs($owner)->postJson('/api/v1/wallet/checkout/orders', [
            'network_code' => 'ETH_SEPOLIA',
            'asset_symbol' => 'ETH',
            'expected_amount_base_units' => '5000000000000000',
            'title' => 'Featured Boost Plan',
        ])->assertCreated();

        $reference = $res->json('order.order_reference');
        $this->assertNotEmpty($reference);

        $this->actingAs($owner)
            ->getJson("/api/v1/wallet/checkout/orders/{$reference}")
            ->assertOk()
            ->assertJsonPath('order.status', CryptoCheckoutOrder::STATUS_PENDING);

        $this->actingAs($other)
            ->getJson("/api/v1/wallet/checkout/orders/{$reference}")
            ->assertStatus(404);
    }

    public function test_checkout_order_reconciles_exact_underpaid_overpaid_and_expired_deposits(): void
    {
        $net = BlockchainNetwork::where('network_code', 'ETH_SEPOLIA')->firstOrFail();
        $eth = CryptoAsset::where('network_id', $net->id)->where('symbol', 'ETH')->firstOrFail();
        $checkout = app(CryptoCheckoutService::class);
        $detector = app(DepositDetector::class);
        $verifier = app(DepositVerifier::class);
        $creditor = app(DepositCreditService::class);

        // 1. Exact Match -> PAID
        $u1 = User::factory()->create();
        $o1 = $checkout->createOrder($u1, $net, $eth, '5000000000000000', 'Exact Order');
        $d1 = $detector->recordObservedDeposit($o1->walletAddress, [
            'tx_hash' => '0x' . str_repeat('1', 64),
            'to_address' => $o1->walletAddress->address,
            'amount_base_units' => '5000000000000000',
            'confirmations' => 6,
        ]);
        $creditor->creditVerifiedDeposit($verifier->verify($d1), false);
        $this->assertSame(CryptoCheckoutOrder::STATUS_PAID, $o1->fresh()->status);

        // 2. Underpayment -> UNDERPAID
        $u2 = User::factory()->create();
        $o2 = $checkout->createOrder($u2, $net, $eth, '5000000000000000', 'Underpaid Order');
        $d2 = $detector->recordObservedDeposit($o2->walletAddress, [
            'tx_hash' => '0x' . str_repeat('2', 64),
            'to_address' => $o2->walletAddress->address,
            'amount_base_units' => '3000000000000000',
            'confirmations' => 6,
        ]);
        $creditor->creditVerifiedDeposit($verifier->verify($d2), false);
        $this->assertSame(CryptoCheckoutOrder::STATUS_UNDERPAID, $o2->fresh()->status);
        $this->assertSame('2000000000000000', $o2->fresh()->metadata['shortfall_base_units']);

        // 3. Overpayment -> OVERPAID
        $u3 = User::factory()->create();
        $o3 = $checkout->createOrder($u3, $net, $eth, '5000000000000000', 'Overpaid Order');
        $d3 = $detector->recordObservedDeposit($o3->walletAddress, [
            'tx_hash' => '0x' . str_repeat('3', 64),
            'to_address' => $o3->walletAddress->address,
            'amount_base_units' => '7000000000000000',
            'confirmations' => 6,
        ]);
        $creditor->creditVerifiedDeposit($verifier->verify($d3), false);
        $this->assertSame(CryptoCheckoutOrder::STATUS_OVERPAID, $o3->fresh()->status);
        $this->assertSame('2000000000000000', $o3->fresh()->metadata['overpaid_base_units']);

        // 4. Expired -> EXPIRED (late_payment = true)
        $u4 = User::factory()->create();
        $o4 = $checkout->createOrder($u4, $net, $eth, '5000000000000000', 'Expired Order');
        $o4->update(['expires_at' => now()->subMinute()]);
        $d4 = $detector->recordObservedDeposit($o4->walletAddress, [
            'tx_hash' => '0x' . str_repeat('4', 64),
            'to_address' => $o4->walletAddress->address,
            'amount_base_units' => '5000000000000000',
            'confirmations' => 6,
        ]);
        $creditor->creditVerifiedDeposit($verifier->verify($d4), false);
        $this->assertSame(CryptoCheckoutOrder::STATUS_EXPIRED, $o4->fresh()->status);
        $this->assertTrue($o4->fresh()->metadata['late_payment']);
    }
}
