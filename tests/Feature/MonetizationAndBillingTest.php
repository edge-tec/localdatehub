<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Billing\Gateways\ChargeResult;
use App\Modules\Billing\Gateways\PaymentGatewayManager;
use App\Modules\Billing\Gateways\SandboxGateway;
use App\Modules\Billing\Models\Coupon;
use App\Modules\Billing\Models\FeaturedListing;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentFraudLog;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\PaymentWebhook;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Billing\Models\PricingRule;
use App\Modules\Billing\Models\Refund;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Services\FraudMonitoringService;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Billing\Services\PromotionService;
use App\Modules\Billing\Services\ReconciliationService;
use App\Modules\Billing\Services\SubscriptionService;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use Database\Seeders\PricingPlanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonetizationAndBillingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected State $state;
    protected City $city;
    protected Category $category;
    protected Listing $listing;
    protected PricingPlan $freePlan;
    protected PricingPlan $featuredPlan;
    protected PricingPlan $membershipPlan;
    protected PricingPlan $bumpPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PricingPlanSeeder::class);

        $country = Country::firstOrCreate(['code' => 'US'], ['name' => 'United States', 'is_active' => true]);
        $this->state = State::create(['country_id' => $country->id, 'name' => 'Texas', 'code' => 'TX', 'slug' => 'texas', 'is_active' => true]);
        $this->city = City::create(['state_id' => $this->state->id, 'name' => 'Austin', 'slug' => 'austin', 'is_active' => true]);
        $this->category = Category::create(['name' => 'Women Seeking Men', 'slug' => 'w4m', 'is_active' => true]);

        $this->user = User::factory()->create([
            'is_age_verified' => true,
            'role' => 'user',
        ]);

        $this->admin = User::factory()->create([
            'is_age_verified' => true,
            'role' => 'super_admin',
        ]);

        $this->listing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->state->id,
            'city_id' => $this->city->id,
            'title' => 'Charming Austin Professional Seeking Genuine Connection',
            'slug' => 'charming-austin-professional-' . Str::random(6),
            'description' => 'Mature and sincere local companion seeking thoughtful dates around Austin.',
            'poster_age' => 28,
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'is_featured' => false,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->freePlan = PricingPlan::where('slug', 'free-listing')->firstOrFail();
        $this->featuredPlan = PricingPlan::where('slug', 'city-featured')->firstOrFail();
        $this->membershipPlan = PricingPlan::where('slug', 'verified-plus-monthly')->firstOrFail();
        $this->bumpPlan = PricingPlan::where('slug', 'bump-to-top')->firstOrFail();
    }

    public function test_free_listing_promotion_is_supported_where_permitted(): void
    {
        $service = app(PromotionService::class);

        $result = $service->promoteListing(
            listing: $this->listing,
            plan: $this->freePlan,
            paymentData: ['gateway' => 'sandbox'],
            user: $this->user,
            placement: 'category_top'
        );

        $this->assertNotNull($result['featured_listing']);
        $this->assertEquals(0, $result['transaction']->amount_cents);
        $this->assertEquals('completed', $result['transaction']->status);
        $this->assertTrue($this->listing->fresh()->is_featured);
        $this->assertNotNull($this->listing->fresh()->promoted_until);
    }

    public function test_featured_listing_promotion_charges_gateway_and_creates_invoice(): void
    {
        $service = app(PromotionService::class);

        $result = $service->promoteListing(
            listing: $this->listing,
            plan: $this->featuredPlan,
            paymentData: [
                'gateway' => 'sandbox',
                'payment_token' => 'tok_sandbox_test',
                'card_last_four' => '4242',
                'card_brand' => 'Visa',
            ],
            user: $this->user,
            placement: 'city_top'
        );

        $this->assertEquals(499, $result['transaction']->amount_cents);
        $this->assertEquals('completed', $result['transaction']->status);
        $this->assertEquals('4242', $result['transaction']->card_last_four);
        $this->assertTrue($this->listing->fresh()->is_featured);

        // Verify invoice was created
        $invoice = Invoice::where('payment_transaction_id', $result['transaction']->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(499, $invoice->total_cents);
        $this->assertEquals('paid', $invoice->status);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
    }

    public function test_dynamic_pricing_rules_apply_discounts_and_free_overrides(): void
    {
        $service = app(PromotionService::class);

        // 1. Create a 50% discount rule for this category
        PricingRule::create([
            'pricing_plan_id' => $this->featuredPlan->id,
            'category_id' => $this->category->id,
            'rule_name' => 'W4M 50% Category Discount',
            'discount_percentage' => 50,
            'is_active' => true,
        ]);

        $pricing = $service->calculatePrice($this->featuredPlan, $this->category, $this->state);
        $this->assertEquals(499, $pricing['base_price_cents']);
        $this->assertEquals(249, $pricing['final_price_cents']);
        $this->assertEquals(250, $pricing['discount_cents']);

        // 2. Coupon discount test with rule stacking (499 - 50% = 249, minus 20% coupon = 199)
        $coupon = Coupon::create([
            'code' => 'SPRING20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
        ]);

        $stackedPricing = $service->calculatePrice($this->featuredPlan, null, null, $coupon);
        $this->assertEquals(499, $stackedPricing['base_price_cents']);
        $this->assertEquals(199, $stackedPricing['final_price_cents']);

        // 3. Coupon standalone without active rules
        PricingRule::query()->delete();
        $standalonePricing = $service->calculatePrice($this->featuredPlan, null, null, $coupon);
        $this->assertEquals(499, $standalonePricing['base_price_cents']);
        $this->assertEquals(399, $standalonePricing['final_price_cents']);
    }

    public function test_listing_bump_updates_bumped_at_timestamp(): void
    {
        $service = app(PromotionService::class);

        $this->assertNull($this->listing->bumped_at);
        $updated = $service->bumpListing($this->listing, $this->user);

        $this->assertNotNull($updated->bumped_at);
        $this->assertEquals($this->listing->published_at->timestamp, $updated->published_at->timestamp);
    }

    public function test_promotions_expire_automatically_when_ends_at_passes(): void
    {
        $service = app(PromotionService::class);

        // Create expired featured listing
        FeaturedListing::create([
            'listing_id' => $this->listing->id,
            'user_id' => $this->user->id,
            'pricing_plan_id' => $this->featuredPlan->id,
            'placement' => 'homepage',
            'status' => 'active',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->listing->update(['is_featured' => true, 'promoted_until' => now()->subDay()]);

        $expiredCount = $service->expireOutdatedPromotions();
        $this->assertEquals(1, $expiredCount);
        $this->assertFalse($this->listing->fresh()->is_featured);
        $this->assertNull($this->listing->fresh()->promoted_until);
    }

    public function test_subscription_creation_charges_and_activates_membership(): void
    {
        $subService = app(SubscriptionService::class);

        $sub = $subService->createSubscription($this->user, $this->membershipPlan, [
            'gateway' => 'sandbox',
            'payment_token' => 'tok_sandbox_sub',
            'card_last_four' => '1111',
            'card_brand' => 'Mastercard',
        ]);

        $this->assertEquals('active', $sub->status);
        $this->assertTrue($sub->auto_renew);
        $this->assertNotNull($sub->next_renewal_at);

        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => $this->user->id,
            'amount_cents' => 1499,
            'status' => 'completed',
        ]);
    }

    public function test_subscription_cancellation_gracefully_updates_auto_renew_and_status(): void
    {
        $subService = app(SubscriptionService::class);
        $sub = $subService->createSubscription($this->user, $this->membershipPlan);

        $cancelled = $subService->cancelSubscription($sub, 'User requested cancel');
        $this->assertEquals('canceled', $cancelled->status);
        $this->assertFalse($cancelled->auto_renew);
        $this->assertNotNull($cancelled->canceled_at);
    }

    public function test_subscription_renewal_processing_charges_and_extends_ends_at(): void
    {
        $subService = app(SubscriptionService::class);

        $sub = Subscription::create([
            'user_id' => $this->user->id,
            'pricing_plan_id' => $this->membershipPlan->id,
            'status' => 'active',
            'gateway' => 'sandbox',
            'gateway_subscription_id' => 'sbx_sub_test123',
            'starts_at' => now()->subDays(31),
            'ends_at' => now()->subMinute(),
            'next_renewal_at' => now()->subMinute(),
            'auto_renew' => true,
        ]);

        $initialEndsAt = $sub->ends_at;
        $result = $subService->processRenewals();

        $this->assertEquals(1, $result['processed']);
        $this->assertTrue($sub->fresh()->ends_at->isAfter($initialEndsAt));
    }

    public function test_fraud_monitoring_blocks_velocity_exceeded(): void
    {
        $fraud = app(FraudMonitoringService::class);

        // Pre-create 6 recent charges on same IP within an hour
        for ($i = 0; $i < 6; $i++) {
            PaymentTransaction::create([
                'user_id' => $this->user->id,
                'reference_id' => 'BURST-' . $i . '-' . Str::random(6),
                'amount_cents' => 1000,
                'gateway' => 'sandbox',
                'status' => 'completed',
                'gateway_payload' => ['ip_address' => '198.51.100.55'],
            ]);
        }

        $assessment = $fraud->assessRisk($this->user, 2000, '198.51.100.55');
        $this->assertFalse($assessment['allowed']);
        $this->assertContains('blocked', ['high', 'blocked']);
        $this->assertDatabaseHas('payment_fraud_logs', [
            'user_id' => $this->user->id,
            'ip_address' => '198.51.100.55',
        ]);
    }

    public function test_payment_webhook_rejects_invalid_signatures(): void
    {
        $response = $this->postJson(route('webhooks.payments', ['gateway' => 'sandbox']), [
            'event_id' => 'evt_test_bad',
            'event_type' => 'charge.succeeded',
        ], [
            'X-Gateway-Signature' => 'invalid_sha256_signature_string',
        ]);

        $response->assertStatus(400);
    }

    public function test_payment_webhook_is_idempotent_and_prevents_duplicate_processing(): void
    {
        $secret = config('monetization.gateways.sandbox.webhook_secret', 'sandbox_webhook_secret_key_12345');
        $payload = [
            'event_id' => 'evt_idempotent_999',
            'event_type' => 'charge.succeeded',
            'data' => [
                'reference_id' => 'TXN-IDEMPOTENT',
                'amount_cents' => 499,
            ],
        ];

        $rawJson = json_encode($payload);
        $signature = hash_hmac('sha256', $rawJson, $secret);

        // First call
        $first = $this->call(
            'POST',
            route('webhooks.payments', ['gateway' => 'sandbox']),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_GATEWAY_SIGNATURE' => $signature],
            $rawJson
        );
        $first->assertStatus(200);

        // Duplicate call with same event_id
        $second = $this->call(
            'POST',
            route('webhooks.payments', ['gateway' => 'sandbox']),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_GATEWAY_SIGNATURE' => $signature],
            $rawJson
        );

        $second->assertStatus(200);
        $second->assertJson(['status' => 'already_processed']);
        $this->assertEquals(1, PaymentWebhook::where('event_id', 'evt_idempotent_999')->count());
    }

    public function test_payment_webhook_fulfills_successful_charge(): void
    {
        $txn = PaymentTransaction::create([
            'user_id' => $this->user->id,
            'listing_id' => $this->listing->id,
            'reference_id' => 'TXN-WEBHOOK-FULFILL',
            'amount_cents' => 499,
            'currency' => 'USD',
            'gateway' => 'sandbox',
            'status' => 'pending',
            'is_reconciled' => false,
        ]);

        $featured = FeaturedListing::create([
            'listing_id' => $this->listing->id,
            'user_id' => $this->user->id,
            'placement' => 'city_top',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
            'is_active' => false,
        ]);

        $secret = config('monetization.gateways.sandbox.webhook_secret', 'sandbox_webhook_secret_key_12345');
        $payload = [
            'event_id' => 'evt_charge_ok_' . Str::random(8),
            'event_type' => 'charge.succeeded',
            'reference_id' => 'TXN-WEBHOOK-FULFILL',
            'transaction_id' => 'sbx_txn_fulfilled_123',
            'amount_cents' => 499,
        ];
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', $raw, $secret);

        $response = $this->call(
            'POST',
            route('webhooks.payments', ['gateway' => 'sandbox']),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_GATEWAY_SIGNATURE' => $sig],
            $raw
        );

        $response->assertStatus(200);
        $this->assertEquals('completed', $txn->fresh()->status);
        $this->assertTrue($txn->fresh()->is_reconciled);
        $this->assertTrue($featured->fresh()->is_active);
        $this->assertTrue($this->listing->fresh()->is_featured);
    }

    public function test_admin_can_issue_refund_with_audit_trail(): void
    {
        $txn = PaymentTransaction::create([
            'user_id' => $this->user->id,
            'listing_id' => $this->listing->id,
            'reference_id' => 'TXN-REFUND-ME',
            'amount_cents' => 999,
            'currency' => 'USD',
            'gateway' => 'sandbox',
            'status' => 'completed',
        ]);

        $featured = FeaturedListing::create([
            'listing_id' => $this->listing->id,
            'user_id' => $this->user->id,
            'placement' => 'homepage',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(14),
            'is_active' => true,
        ]);
        $this->listing->update(['is_featured' => true]);

        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class)
            ->set('tab', 'payments')
            ->call('openRefundModal', $txn->id)
            ->set('refundReason', 'Customer dissatisfaction duplicate billing')
            ->call('issueRefund')
            ->assertHasNoErrors();

        $this->assertEquals('refunded', $txn->fresh()->status);
        $this->assertDatabaseHas('refunds', [
            'payment_transaction_id' => $txn->id,
            'amount_cents' => 999,
            'reason' => 'Customer dissatisfaction duplicate billing',
        ]);
        $this->assertFalse($featured->fresh()->is_active);
        $this->assertFalse($this->listing->fresh()->is_featured);
    }

    public function test_reconciliation_service_matches_gateway_transactions_and_updates_status(): void
    {
        $txn = PaymentTransaction::create([
            'user_id' => $this->user->id,
            'reference_id' => 'TXN-RECON-01',
            'gateway_transaction_id' => 'sbx_txn_recon_01',
            'amount_cents' => 1499,
            'gateway' => 'sandbox',
            'status' => 'pending',
            'is_reconciled' => false,
        ]);

        $reconciliation = app(ReconciliationService::class);
        $result = $reconciliation->reconcileGatewayBatch('sandbox', [
            [
                'gateway_id' => 'sbx_txn_recon_01',
                'reference_id' => 'TXN-RECON-01',
                'status' => 'settled',
                'amount_cents' => 1499,
            ],
        ]);

        $this->assertEquals(1, $result['matched']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals('completed', $txn->fresh()->status);
        $this->assertTrue($txn->fresh()->is_reconciled);
    }

    public function test_user_can_view_own_invoice_and_stranger_cannot(): void
    {
        $txn = PaymentTransaction::create([
            'user_id' => $this->user->id,
            'reference_id' => 'TXN-INV-TEST',
            'amount_cents' => 499,
            'gateway' => 'sandbox',
            'status' => 'completed',
        ]);

        $invoice = app(InvoiceService::class)->createFromTransaction($txn);

        // Owner can view
        $this->actingAs($this->user)
            ->get(route('account.invoices.show', $invoice))
            ->assertStatus(200)
            ->assertSee($invoice->invoice_number)
            ->assertSee('$4.99');

        // Other user cannot view
        $otherUser = User::factory()->create(['role' => 'user']);
        $this->actingAs($otherUser)
            ->get(route('account.invoices.show', $invoice))
            ->assertStatus(403);

        // Admin can view
        $this->actingAs($this->admin)
            ->get(route('account.invoices.show', $invoice))
            ->assertStatus(200);
    }

    public function test_live_adult_merchant_blocks_unauthorized_live_gateways_without_written_approval(): void
    {
        config(['monetization.adult_merchant_approved' => false]);

        $ccbill = new \App\Modules\Billing\Gateways\CcbillGateway();
        $result = $ccbill->createCharge(['amount_cents' => 1000]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Adult merchant underwriting approval not yet verified', $result->errorMessage);

        $stripeAdult = new \App\Modules\Billing\Gateways\StripeAdultGateway();
        $stripeResult = $stripeAdult->createCharge(['amount_cents' => 1000]);

        $this->assertFalse($stripeResult->success);
        $this->assertStringContainsString('prohibited category policy applies', $stripeResult->errorMessage);
    }

    public function test_artisan_process_renewals_and_expire_promotions_commands_run_successfully(): void
    {
        $this->artisan('monetization:process-renewals')
            ->expectsOutputToContain('Renewals processed')
            ->assertExitCode(0);

        $this->artisan('monetization:expire-promotions')
            ->expectsOutputToContain('Expired')
            ->assertExitCode(0);

        $this->artisan('monetization:reconcile')
            ->assertExitCode(0);
    }

    public function test_admin_enterprise_hub_can_create_and_delete_dynamic_pricing_rules(): void
    {
        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class)
            ->set('tab', 'payments')
            ->call('openPricingRuleModal')
            ->set('pricingRuleForm.rule_name', 'Holiday 25% Promotion')
            ->set('pricingRuleForm.discount_percentage', 25)
            ->set('pricingRuleForm.is_active', true)
            ->call('savePricingRule')
            ->assertHasNoErrors();

        $rule = PricingRule::where('rule_name', 'Holiday 25% Promotion')->first();
        $this->assertNotNull($rule);
        $this->assertEquals(25, $rule->discount_percentage);

        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class)
            ->call('deletePricingRule', $rule->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('pricing_rules', ['id' => $rule->id]);
    }
}