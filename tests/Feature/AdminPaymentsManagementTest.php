<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Billing\Models\PaymentFraudLog;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Billing\Models\PricingRule;
use App\Modules\Billing\Models\Refund;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Monetization\Models\WalletTransaction;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Models\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPaymentsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected PaymentTransaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->customer = User::factory()->create([
            'name' => 'Alice Customer',
            'email' => 'alice@testpay.com',
            'role' => 'user',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->transaction = PaymentTransaction::create([
            'user_id' => $this->customer->id,
            'reference_id' => 'TXN-TEST-1001',
            'amount_cents' => 499,
            'currency' => 'USD',
            'gateway' => 'sandbox',
            'gateway_transaction_id' => 'gw_test_123',
            'status' => 'completed',
            'is_reconciled' => false,
        ]);
    }

    public function test_admin_can_view_payments_hub_with_kpi_cards_and_sub_tabs(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->assertOk()
            ->assertSee('Gross Platform Revenue')
            ->assertSee('Crypto Invoices')
            ->assertSee('Circulating Credits')
            ->assertSee('Disputes')
            ->assertSee('All Gateway Transactions')
            ->assertSee('Credit Wallet Ledger')
            ->assertSee('Dynamic Pricing Rules')
            ->assertSee('TXN-TEST-1001')
            ->assertSee('$4.99')
            ->assertSee('Alice Customer');
    }

    public function test_admin_can_filter_and_search_transactions(): void
    {
        PaymentTransaction::create([
            'user_id' => $this->customer->id,
            'reference_id' => 'TXN-STRIPE-999',
            'amount_cents' => 1999,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'status' => 'pending',
            'is_reconciled' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->set('paymentSearch', 'STRIPE')
            ->assertSee('TXN-STRIPE-999')
            ->assertDontSee('TXN-TEST-1001')
            ->set('paymentSearch', '')
            ->set('paymentGatewayFilter', 'stripe')
            ->assertSee('TXN-STRIPE-999')
            ->assertDontSee('TXN-TEST-1001')
            ->call('resetPaymentFilters')
            ->assertSee('TXN-TEST-1001')
            ->assertSee('TXN-STRIPE-999');
    }

    public function test_admin_can_open_and_view_transaction_receipt_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->assertSet('showViewTransactionModal', false)
            ->call('openViewTransactionModal', $this->transaction->id)
            ->assertSet('showViewTransactionModal', true)
            ->assertSee('Transaction Receipt')
            ->assertSee('TXN-TEST-1001')
            ->assertSee('Alice Customer')
            ->assertSee('alice@testpay.com')
            ->call('closeModals')
            ->assertSet('showViewTransactionModal', false);
    }

    public function test_admin_can_switch_to_crypto_tab_and_view_order(): void
    {
        $gateway = PaymentGateway::firstOrCreate(
            ['slug' => 'sandbox_crypto'],
            [
                'name' => 'Mock Crypto Gateway',
                'provider' => 'nowpayments',
                'is_enabled' => true,
                'environment' => 'sandbox',
            ]
        );

        $crypto = CryptoPayment::create([
            'user_id' => $this->customer->id,
            'gateway_id' => $gateway->id,
            'internal_order_id' => 'CPKG-TEST-CRYPTO-777',
            'payment_currency' => 'USDT',
            'payment_network' => 'TRC20',
            'expected_amount' => 50.00000000,
            'received_amount' => 50.00000000,
            'fiat_amount' => 50.00,
            'fiat_currency' => 'USD',
            'confirmations' => 1,
            'required_confirmations' => 1,
            'status' => CryptoPayment::STATUS_COMPLETED,
            'expires_at' => now()->addHour(),
            'payment_address' => 'TTestTrxAddress123456789',
            'transaction_hash' => 'hash_test_123456789',
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('setPaymentSubTab', 'crypto')
            ->assertSet('paymentSubTab', 'crypto')
            ->assertSee('CPKG-TEST-CRYPTO-777')
            ->assertSee('50.00000000 USDT')
            ->assertSee('TRC20')
            ->call('openViewCryptoModal', $crypto->id)
            ->assertSet('showViewCryptoModal', true)
            ->assertSee('Web3 Cryptocurrency Order')
            ->assertSee('TTestTrxAddress123456789')
            ->call('closeModals')
            ->assertSet('showViewCryptoModal', false);
    }

    public function test_admin_can_switch_to_wallet_ledger_sub_tab(): void
    {
        WalletTransaction::create([
            'user_id' => $this->customer->id,
            'reference' => 'WTX-TEST-555',
            'type' => 'CREDIT_PURCHASE',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'description' => 'Test credit pack purchase',
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('setPaymentSubTab', 'wallet')
            ->assertSet('paymentSubTab', 'wallet')
            ->assertSee('WTX-TEST-555')
            ->assertSee('+100 credits')
            ->assertSee('Test credit pack purchase');
    }

    public function test_admin_can_issue_refund(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('openRefundModal', $this->transaction->id)
            ->assertSet('showRefundModal', true)
            ->set('refundReason', 'Customer requested cancellation within guarantee window')
            ->call('issueRefund')
            ->assertSet('showRefundModal', false)
            ->assertHasNoErrors();

        $this->transaction->refresh();
        $this->assertEquals('refunded', $this->transaction->status);

        $this->assertDatabaseHas('refunds', [
            'payment_transaction_id' => $this->transaction->id,
            'amount_cents' => 499,
            'reason' => 'Customer requested cancellation within guarantee window',
        ]);
    }

    public function test_admin_can_manage_dynamic_pricing_rules(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('openPricingRuleModal')
            ->assertSet('showPricingRuleModal', true)
            ->set('pricingRuleForm.rule_name', 'Black Friday 50% Off')
            ->set('pricingRuleForm.discount_percentage', 50)
            ->set('pricingRuleForm.is_free', false)
            ->set('pricingRuleForm.is_active', true)
            ->call('savePricingRule')
            ->assertSet('showPricingRuleModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pricing_rules', [
            'rule_name' => 'Black Friday 50% Off',
            'discount_percentage' => 50,
        ]);

        $rule = PricingRule::where('rule_name', 'Black Friday 50% Off')->first();
        $component->call('deletePricingRule', $rule->id);

        $this->assertDatabaseMissing('pricing_rules', [
            'id' => $rule->id,
        ]);
    }

    public function test_admin_can_view_subscriptions_with_metrics_and_manage_lifecycle(): void
    {
        $plan = PricingPlan::first() ?? PricingPlan::create([
            'name' => 'Gold VIP',
            'slug' => 'gold-vip',
            'description' => 'VIP Tier',
            'price_cents' => 2999,
            'currency' => 'USD',
            'duration_days' => 30,
            'plan_type' => 'recurring',
            'features' => ['Unlimited Messaging', 'Priority Placement'],
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'user_id' => $this->customer->id,
            'pricing_plan_id' => $plan->id,
            'gateway' => 'inhouse_crypto',
            'gateway_subscription_id' => 'SUB-CRYPTO-999',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
            'status' => 'active',
            'auto_renew' => true,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'subscriptions'])
            ->assertSee('Monthly Recurring Revenue')
            ->assertSee('Active Subscribers')
            ->assertSee('Subscribers')
            ->assertSee('Subscription Plans')
            ->assertSee($plan->name)
            ->assertSee('SUB-CRYPTO-999');

        // Toggle sub-tab to plans
        $component->call('setSubscriptionSubTab', 'plans')
            ->assertSet('subscriptionSubTab', 'plans')
            ->assertSee($plan->name);

        // Switch back to subscribers
        $component->call('setSubscriptionSubTab', 'subscribers')
            ->assertSet('subscriptionSubTab', 'subscribers');

        // Toggle Auto-Renew
        $component->call('toggleAutoRenew', $subscription->id);
        $this->assertFalse((bool) $subscription->fresh()->auto_renew);

        // Extend Subscription by 30 days
        $initialEndsAt = $subscription->ends_at;
        $component->call('extendSubscription', $subscription->id, 30);
        $this->assertTrue($subscription->fresh()->ends_at->greaterThan($initialEndsAt));

        // Open View Details Modal
        $component->call('openViewSubscriptionModal', $subscription->id)
            ->assertSet('showViewSubscriptionModal', true)
            ->assertSee('Membership Record #' . $subscription->id);

        // Cancel Subscription
        $component->call('cancelSubscription', $subscription->id);
        $this->assertEquals('canceled', $subscription->fresh()->status);
    }

    public function test_admin_can_assign_subscription_manually(): void
    {
        $plan = PricingPlan::first() ?? PricingPlan::create([
            'name' => 'Diamond VIP',
            'slug' => 'diamond-vip',
            'description' => 'Diamond Tier',
            'price_cents' => 9999,
            'currency' => 'USD',
            'duration_days' => 60,
            'plan_type' => 'recurring',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'subscriptions'])
            ->call('openAssignSubscriptionModal')
            ->assertSet('showAssignSubscriptionModal', true)
            ->set('assignSubscriptionForm.user_id', $this->customer->id)
            ->set('assignSubscriptionForm.pricing_plan_id', $plan->id)
            ->set('assignSubscriptionForm.duration_days', 45)
            ->set('assignSubscriptionForm.gateway', 'manual_admin')
            ->set('assignSubscriptionForm.status', 'active')
            ->set('assignSubscriptionForm.auto_renew', true)
            ->call('saveAssignedSubscription')
            ->assertSet('showAssignSubscriptionModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->customer->id,
            'pricing_plan_id' => $plan->id,
            'gateway' => 'manual_admin',
            'status' => 'active',
            'auto_renew' => true,
        ]);
    }
}
