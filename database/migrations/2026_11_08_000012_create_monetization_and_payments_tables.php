<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 12: Monetization, Pricing, Subscriptions, Payments & Webhooks
     */
    public function up(): void
    {
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->string('plan_type', 30); // listing_upgrade, membership, featured_spot, bump
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['plan_type', 'is_active']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pricing_plan_id')->constrained('pricing_plans')->cascadeOnDelete();
            $table->string('status', 30)->default('active'); // active, past_due, canceled, expired
            $table->string('gateway', 30)->default('stripe');
            $table->string('gateway_subscription_id', 150)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('featured_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pricing_plan_id')->nullable()->constrained('pricing_plans')->nullOnDelete();
            $table->string('placement', 40)->default('category_top'); // homepage, state_top, city_top, category_top
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['listing_id', 'is_active']);
            $table->index(['placement', 'is_active', 'ends_at']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pricing_plan_id')->nullable()->constrained('pricing_plans')->nullOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
            $table->string('reference_id', 100)->unique();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method', 30)->default('card');
            $table->string('gateway', 30)->default('stripe');
            $table->string('gateway_transaction_id', 150)->nullable();
            $table->string('status', 30)->default('pending'); // pending, completed, failed, refunded
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('reference_id');
        });

        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 30);
            $table->string('event_id', 150)->nullable();
            $table->string('event_type', 100);
            $table->longText('payload');
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'event_type']);
            $table->index('is_processed');
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('discount_type', 20)->default('percentage'); // percentage, fixed
            $table->unsignedInteger('discount_value'); // percentage or cents
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->constrained('payment_transactions')->cascadeOnDelete();
            $table->foreignId('processed_by_admin_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('reason', 150);
            $table->string('status', 30)->default('completed');
            $table->string('gateway_refund_id', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('payment_webhooks');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('featured_listings');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('pricing_plans');
    }
};
