<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Services\ListingManagementService;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Monetization\Models\AccountPostingLimit;
use App\Modules\Monetization\Models\CreditPackage;
use App\Modules\Monetization\Models\PostingPricingRule;
use App\Modules\Monetization\Models\PostingSetting;
use App\Modules\Monetization\Models\UserPostingOverride;
use App\Modules\Monetization\Models\WalletTransaction;
use App\Modules\Monetization\Services\ListingApprovalService;
use App\Modules\Monetization\Services\PostingPricingService;
use App\Modules\Monetization\Services\PostingQuotaService;
use App\Modules\Monetization\Services\UserWalletService;
use App\Modules\Verification\Models\IdentityVerification;
use App\Modules\Verification\Services\IdentityVerificationService;
use App\Support\Exceptions\InsufficientCreditsException;
use Database\Seeders\PostingMonetizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterprisePostingMonetizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(PostingMonetizationSeeder::class);
        $this->seed(\Database\Seeders\CryptoPaymentSeeder::class);
        $this->seed(\Database\Seeders\InHouseCryptoGatewaySeeder::class);
    }

    public function test_new_user_has_free_quota_and_correct_account_type(): void
    {
        $user = User::factory()->create([
            'created_at' => now(),
            'is_identity_verified' => false,
        ]);

        $quotaService = app(PostingQuotaService::class);
        $summary = $quotaService->getUserQuotaSummary($user);

        $this->assertEquals('new_account', $summary['account_type']);
        $this->assertTrue($summary['can_post']);
        $this->assertEquals(3, $summary['free_quota']);
        $this->assertEquals(3, $summary['free_remaining']);
    }

    public function test_pricing_hierarchy_enforcement(): void
    {
        $user = User::factory()->create(['is_identity_verified' => false]);
        $category = Category::first() ?: Category::create([
            'name' => 'Men Seeking Women',
            'slug' => 'men-seeking-women',
            'is_active' => true,
        ]);

        $pricingService = app(PostingPricingService::class);
        $settings = PostingSetting::instance();
        $settings->update([
            'global_posting_mode' => 'paid',
            'default_post_price_credits' => 15,
        ]);

        // Clear account-type limits so it falls back to global default
        AccountPostingLimit::where('account_type', $user->getAccountType())->update([
            'post_price_credits' => null,
        ]);

        // 1. Falls back to global default (15 credits)
        $price1 = $pricingService->calculatePostingPrice($user, $category->id);
        $this->assertEquals(15, $price1['price_credits']);

        // 2. Account-type default override (5 credits for verified via real ID approval)
        $reviewer = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        IdentityVerification::create([
            'user_id' => $user->id,
            'document_type' => 'passport',
            'document_front_path' => 'verifications/front.jpg',
            'selfie_path' => 'verifications/selfie.jpg',
            'status' => IdentityVerification::STATUS_VERIFIED,
            'reviewer_id' => $reviewer->id,
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
        ]);
        app(IdentityVerificationService::class)->syncUserBadgeState($user->fresh());
        $price2 = $pricingService->calculatePostingPrice($user->fresh(), $category->id);
        $this->assertEquals(5, $price2['price_credits']);

        // 3. Category-specific rule override (25 credits)
        PostingPricingRule::create([
            'category_id' => $category->id,
            'price_credits' => 25,
            'priority' => 10,
            'is_active' => true,
        ]);
        $price3 = $pricingService->calculatePostingPrice($user, $category->id);
        $this->assertEquals(25, $price3['price_credits']);

        // 4. Individual user override (3 credits)
        UserPostingOverride::create([
            'user_id' => $user->id,
            'custom_price_credits' => 3,
            'approval_mode' => 'inherit',
        ]);
        $price4 = $pricingService->calculatePostingPrice($user, $category->id);
        $this->assertEquals(3, $price4['price_credits']);
    }

    public function test_user_wallet_credit_debit_and_insufficient_balance_rejection(): void
    {
        $user = User::factory()->create();
        $walletService = app(UserWalletService::class);

        // Initial balance is 0
        $this->assertEquals(0, $user->getWalletBalance());

        // Credit 100 credits
        $creditTx = $walletService->credit(
            $user,
            100,
            WalletTransaction::TYPE_CREDIT_PURCHASE,
            'Purchased Starter Pack'
        );
        $this->assertEquals(100, $user->getWalletBalance());
        $this->assertEquals(100, $creditTx->balance_after);

        // Debit 20 credits
        $debitTx = $walletService->debit(
            $user,
            20,
            WalletTransaction::TYPE_POST_CREATION_DEBIT,
            'Ad publishing fee'
        );
        $this->assertEquals(80, $user->getWalletBalance());
        $this->assertEquals(80, $debitTx->balance_after);

        // Attempt debit exceeding balance (90 credits when balance is 80)
        $this->expectException(InsufficientCreditsException::class);
        $walletService->debit(
            $user,
            90,
            WalletTransaction::TYPE_POST_CREATION_DEBIT,
            'Failed debit'
        );
    }

    public function test_listing_creation_debits_wallet_and_auto_approves_verified_user(): void
    {
        $user = User::factory()->create([
            'is_identity_verified' => false,
        ]);
        $reviewer = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        IdentityVerification::create([
            'user_id' => $user->id,
            'document_type' => 'passport',
            'document_front_path' => 'verifications/front.jpg',
            'selfie_path' => 'verifications/selfie.jpg',
            'status' => IdentityVerification::STATUS_VERIFIED,
            'reviewer_id' => $reviewer->id,
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
        ]);
        app(IdentityVerificationService::class)->syncUserBadgeState($user);
        $user = $user->fresh();

        $city = City::first();
        $state = $city->state;
        $category = Category::first();

        // Credit wallet
        $walletService = app(UserWalletService::class);
        $walletService->credit($user, 50, WalletTransaction::TYPE_CREDIT_PURCHASE, 'Initial credit');

        // Set paid posting mode
        PostingSetting::instance()->update([
            'global_posting_mode' => 'paid',
            'default_post_price_credits' => 10,
            'verified_user_auto_approve' => true,
        ]);

        $listingManagement = app(ListingManagementService::class);
        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'title' => 'Verified Poster Romantic Date Post',
            'slug' => 'verified-poster-romantic-date-post-' . uniqid(),
            'description' => 'Looking for exciting genuine local dinner connections in downtown area.',
            'poster_age' => 28,
            'poster_gender' => 'female',
            'intent' => 'dating',
            'status' => Listing::STATUS_DRAFT,
            'published_at' => now(),
        ]);

        $submitted = $listingManagement->submitForReview($listing, $user, [
            'attributes' => ['intent' => 'dating'],
        ]);

        // Verified poster is automatically approved and has verified poster badge
        $this->assertEquals(Listing::STATUS_ACTIVE, $submitted->status);
        $this->assertTrue($submitted->is_verified_poster);

        // Verified user price is 5 credits (account-type default), so 50 - 5 = 45
        $this->assertEquals(45, $user->getWalletBalance());
    }

    public function test_identity_verification_workflow_and_badge_granting(): void
    {
        Storage::fake(IdentityVerificationService::DISK);

        $user = User::factory()->create(['is_identity_verified' => false]);
        $admin = User::factory()->create(['role' => 'admin']);

        $front = UploadedFile::fake()->image('id_front.jpg');
        $back = UploadedFile::fake()->image('id_back.jpg');
        $selfie = UploadedFile::fake()->image('selfie.jpg');

        $verificationService = app(IdentityVerificationService::class);

        // 1. User submits verification
        $verification = $verificationService->submitVerification(
            $user,
            'drivers_license',
            $front,
            $back,
            $selfie
        );

        $this->assertEquals(IdentityVerification::STATUS_PENDING, $verification->status);
        $this->assertFalse($user->fresh()->is_identity_verified);

        // 2. Admin approves verification
        $verificationService->approve($verification, $admin, 'Documents verified clearly.');

        $this->assertEquals(IdentityVerification::STATUS_VERIFIED, $verification->fresh()->status);
        $this->assertTrue($user->fresh()->is_identity_verified);

        // 3. User listings receive verified poster badge
        $city = City::first();
        $state = $city->state;
        $category = Category::first();

        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'title' => 'My Dating Post',
            'slug' => 'my-dating-post-' . uniqid(),
            'description' => 'Great description for test listing.',
            'poster_age' => 25,
            'poster_gender' => 'male',
            'intent' => 'casual',
            'status' => Listing::STATUS_ACTIVE,
            'is_verified_poster' => false,
            'published_at' => now(),
        ]);

        $verificationService->approve($verification, $admin);
        $this->assertTrue($listing->fresh()->is_verified_poster);
    }

    public function test_wallet_checkout_page_renders_successfully(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::first();

        $response = $this->actingAs($user)->get("/account/wallet/checkout/{$package->id}");

        $response->assertStatus(200);
        $response->assertSee($package->name);
        $response->assertSee('Select Payment Provider');
    }

    public function test_wallet_payment_creation_redirects_to_invoice(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::first();
        $gateway = \App\Modules\Payments\Crypto\Models\PaymentGateway::where('is_enabled', true)->first();

        $response = $this->actingAs($user)->post("/account/wallet/checkout/{$package->id}", [
            'gateway_id' => $gateway->id,
            'currency' => 'USDT',
            'network' => 'TRC20',
        ]);

        $response->assertRedirect();
        $payment = \App\Modules\Payments\Crypto\Models\CryptoPayment::latest()->first();
        $this->assertNotNull($payment);
        $this->assertEquals($package->id, $payment->credit_package_id);
        $this->assertEquals('credit_package', $payment->payment_type);
        $response->assertRedirect(route('account.wallet.invoice', $payment));
    }
}
