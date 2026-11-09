<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Posting Settings Table
        Schema::create('posting_settings', function (Blueprint $table) {
            $table->id();
            $table->string('global_posting_mode')->default('quota_then_paid'); // free, paid, quota_then_paid, account_based, category_based
            $table->unsignedInteger('default_post_price_credits')->default(10);
            $table->unsignedInteger('default_free_quota')->default(3);
            $table->boolean('unlimited_posting_enabled')->default(false);
            $table->unsignedInteger('daily_limit')->nullable()->default(5);
            $table->unsignedInteger('weekly_limit')->nullable()->default(20);
            $table->unsignedInteger('monthly_limit')->nullable()->default(50);
            $table->string('approval_mode')->default('conditional'); // auto, manual, conditional
            $table->boolean('new_account_manual_review')->default(true);
            $table->boolean('verified_user_auto_approve')->default(true);
            $table->unsignedInteger('verified_user_ranking_boost')->default(25);
            $table->timestamps();
        });

        // 2. Account Posting Limits Table
        Schema::create('account_posting_limits', function (Blueprint $table) {
            $table->id();
            $table->string('account_type')->unique(); // new_account, regular, verified, premium, business
            $table->unsignedInteger('daily_limit')->nullable()->default(3);
            $table->unsignedInteger('weekly_limit')->nullable()->default(10);
            $table->unsignedInteger('monthly_limit')->nullable()->default(30);
            $table->unsignedInteger('free_post_quota')->default(3);
            $table->boolean('is_unlimited')->default(false);
            $table->unsignedInteger('post_price_credits')->nullable();
            $table->string('approval_mode')->default('inherit'); // inherit, auto, manual
            $table->timestamps();
        });

        // 3. User Posting Overrides Table
        Schema::create('user_posting_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('custom_quota')->nullable();
            $table->unsignedInteger('free_post_allowance')->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->unsignedInteger('custom_price_credits')->nullable();
            $table->string('approval_mode')->default('inherit'); // inherit, auto, manual
            $table->boolean('is_posting_restricted')->default(false);
            $table->text('restriction_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // 4. Posting Pricing Rules Table
        Schema::create('posting_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('account_type')->nullable(); // new_account, regular, verified, premium, business
            $table->unsignedInteger('price_credits')->default(10);
            $table->boolean('is_free')->default(false);
            $table->unsignedInteger('priority')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'account_type', 'is_active'], 'ppr_cat_acc_active_idx');
        });

        // 5. User Wallets Table
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0);
            $table->unsignedBigInteger('total_purchased')->default(0);
            $table->unsignedBigInteger('total_spent')->default(0);
            $table->string('currency')->default('CREDITS');
            $table->timestamps();
        });

        // 6. Credit Packages Table
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('credits');
            $table->unsignedInteger('bonus_credits')->default(0);
            $table->decimal('price_usd', 10, 2);
            $table->unsignedInteger('display_order')->default(1);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 7. Wallet Transactions Ledger Table
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('type'); // CREDIT_PURCHASE, BONUS_CREDIT, POST_CREATION_DEBIT, POST_BOOST_DEBIT, POST_EDIT_DEBIT, REFUND, ADMIN_CREDIT, ADMIN_DEBIT, PROMOTIONAL_CREDIT, TRANSACTION_REVERSAL
            $table->integer('amount'); // signed or absolute (credit amount)
            $table->unsignedBigInteger('balance_before');
            $table->unsignedBigInteger('balance_after');
            $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
            $table->foreignId('crypto_payment_id')->nullable()->constrained('crypto_payments')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->index();
            $table->string('description')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        // 8. Identity Verifications Table
        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_type'); // drivers_license, passport, national_id, state_id
            $table->string('document_front_path');
            $table->string('document_back_path')->nullable();
            $table->string('selfie_path');
            $table->string('status')->default('pending'); // pending, under_review, verified, rejected, additional_info_required, suspended, expired
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // 9. Verification Audit Logs Table
        Schema::create('verification_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_id')->constrained('identity_verifications')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // submitted, under_review, approved, rejected, additional_info_requested, suspended, document_viewed
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['verification_id', 'created_at']);
        });

        // 10. Update crypto_payments table with credit package association
        if (Schema::hasTable('crypto_payments') && !Schema::hasColumn('crypto_payments', 'payment_type')) {
            Schema::table('crypto_payments', function (Blueprint $table) {
                $table->string('payment_type')->default('boost')->after('user_id'); // boost, credit_package
                $table->foreignId('credit_package_id')->nullable()->after('boost_plan_id')->constrained('credit_packages')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('crypto_payments') && Schema::hasColumn('crypto_payments', 'payment_type')) {
            Schema::table('crypto_payments', function (Blueprint $table) {
                $table->dropForeign(['credit_package_id']);
                $table->dropColumn(['payment_type', 'credit_package_id']);
            });
        }

        Schema::dropIfExists('verification_audit_logs');
        Schema::dropIfExists('identity_verifications');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('credit_packages');
        Schema::dropIfExists('user_wallets');
        Schema::dropIfExists('posting_pricing_rules');
        Schema::dropIfExists('user_posting_overrides');
        Schema::dropIfExists('account_posting_limits');
        Schema::dropIfExists('posting_settings');
    }
};

