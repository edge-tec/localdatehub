<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 21: Monetization, Invoices, Pricing Rules, and Fraud Monitoring
     */
    public function up(): void
    {
        // 1. Invoices table
        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
                $table->string('invoice_number', 50)->unique();
                $table->string('status', 30)->default('paid'); // paid, unpaid, refunded, void
                $table->unsignedInteger('subtotal_cents');
                $table->unsignedInteger('tax_cents')->default(0);
                $table->unsignedInteger('total_cents');
                $table->string('currency', 3)->default('USD');
                $table->string('customer_name', 150);
                $table->string('customer_email', 150);
                $table->json('billing_address')->nullable();
                $table->json('line_items');
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('invoice_number');
            });
        }

        // 2. Dynamic Pricing Rules table
        if (! Schema::hasTable('pricing_rules')) {
            Schema::create('pricing_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pricing_plan_id')->nullable()->constrained('pricing_plans')->nullOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
                $table->string('rule_name', 150);
                $table->unsignedTinyInteger('discount_percentage')->default(0);
                $table->unsignedInteger('fixed_price_cents')->nullable();
                $table->boolean('is_free')->default(false);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['pricing_plan_id', 'is_active']);
                $table->index(['category_id', 'state_id']);
            });
        }

        // 3. Payment Fraud & Velocity Monitoring table
        if (! Schema::hasTable('payment_fraud_logs')) {
            Schema::create('payment_fraud_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
                $table->string('ip_address', 45);
                $table->string('card_fingerprint_hash', 64)->nullable();
                $table->unsignedTinyInteger('risk_score')->default(0);
                $table->string('risk_tier', 20)->default('low'); // low, medium, high, blocked
                $table->json('flags')->nullable();
                $table->string('action_taken', 30)->default('allowed'); // allowed, flagged_for_review, auto_declined
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'risk_tier']);
                $table->index('ip_address');
            });
        }

        // 4. Alter listings table if fields missing
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'promoted_until')) {
                $table->timestamp('promoted_until')->nullable()->after('is_featured');
                $table->index('promoted_until');
            }
            if (! Schema::hasColumn('listings', 'bumped_at')) {
                $table->timestamp('bumped_at')->nullable()->after('renewed_at');
                $table->index('bumped_at');
            }
        });

        // 5. Alter payment_transactions table if fields missing
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_transactions', 'card_last_four')) {
                $table->string('card_last_four', 4)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('payment_transactions', 'card_brand')) {
                $table->string('card_brand', 30)->nullable()->after('card_last_four');
            }
            if (! Schema::hasColumn('payment_transactions', 'is_reconciled')) {
                $table->boolean('is_reconciled')->default(false)->after('status');
                $table->timestamp('reconciled_at')->nullable()->after('is_reconciled');
                $table->index('is_reconciled');
            }
        });

        // 6. Alter subscriptions table if fields missing
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'next_renewal_at')) {
                $table->timestamp('next_renewal_at')->nullable()->after('ends_at');
            }
            if (! Schema::hasColumn('subscriptions', 'grace_period_ends_at')) {
                $table->timestamp('grace_period_ends_at')->nullable()->after('next_renewal_at');
            }
            if (! Schema::hasColumn('subscriptions', 'auto_renew')) {
                $table->boolean('auto_renew')->default(true)->after('grace_period_ends_at');
            }
        });

        // 7. Alter featured_listings table if fields missing
        Schema::table('featured_listings', function (Blueprint $table) {
            if (! Schema::hasColumn('featured_listings', 'status')) {
                $table->string('status', 30)->default('active')->after('placement'); // active, expired, pending, deactivated
                $table->index('status');
            }
        });

        // 8. Alter ad_placements table if fields missing
        Schema::table('ad_placements', function (Blueprint $table) {
            if (! Schema::hasColumn('ad_placements', 'payment_transaction_id')) {
                $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete()->after('created_by_admin_id');
            }
            if (! Schema::hasColumn('ad_placements', 'price_cents')) {
                $table->unsignedInteger('price_cents')->default(0)->after('payment_transaction_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_placements', function (Blueprint $table) {
            if (Schema::hasColumn('ad_placements', 'payment_transaction_id')) {
                $table->dropForeign(['payment_transaction_id']);
                $table->dropColumn(['payment_transaction_id', 'price_cents']);
            }
        });

        Schema::table('featured_listings', function (Blueprint $table) {
            if (Schema::hasColumn('featured_listings', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'next_renewal_at')) {
                $table->dropColumn(['next_renewal_at', 'grace_period_ends_at', 'auto_renew']);
            }
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('payment_transactions', 'card_last_four')) {
                $table->dropIndex(['is_reconciled']);
                $table->dropColumn(['card_last_four', 'card_brand', 'is_reconciled', 'reconciled_at']);
            }
        });

        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'promoted_until')) {
                $table->dropIndex(['promoted_until']);
                $table->dropColumn('promoted_until');
            }
            if (Schema::hasColumn('listings', 'bumped_at')) {
                $table->dropIndex(['bumped_at']);
                $table->dropColumn('bumped_at');
            }
        });

        Schema::dropIfExists('payment_fraud_logs');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('invoices');
    }
};