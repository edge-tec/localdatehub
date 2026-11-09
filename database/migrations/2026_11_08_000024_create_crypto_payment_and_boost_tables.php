<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 24: Enterprise Cryptocurrency Payment Gateway & Automatic Post Boost System
     */
    public function up(): void
    {
        // 1. Payment Gateways Table
        if (! Schema::hasTable('payment_gateways')) {
            Schema::create('payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 60)->unique();
                $table->string('provider', 60); // nowpayments, coinpayments, coingate, bitpay, binance_pay, exodus
                $table->boolean('is_enabled')->default(false);
                $table->string('environment', 20)->default('sandbox'); // sandbox, live
                $table->text('encrypted_credentials')->nullable();
                $table->text('webhook_secret_encrypted')->nullable();
                $table->json('supported_currencies')->nullable();
                $table->json('supported_networks')->nullable();
                $table->json('configuration')->nullable();
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->timestamp('last_tested_at')->nullable();
                $table->string('last_test_status', 30)->nullable(); // success, failed
                $table->text('last_test_message')->nullable();
                $table->timestamps();

                $table->index(['provider', 'is_enabled']);
                $table->index('display_order');
            });
        }

        // 2. Boost Plans Table
        if (! Schema::hasTable('boost_plans')) {
            Schema::create('boost_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2)->default(0.00);
                $table->string('currency', 3)->default('USD');
                $table->unsignedSmallInteger('duration_days')->default(7);
                $table->string('featured_position', 60)->default('top_of_search'); // top_of_search, urgent_spotlight, homepage_category
                $table->unsignedSmallInteger('priority_level')->default(1);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'display_order']);
            });
        }

        // 3. Crypto Payments Table
        if (! Schema::hasTable('crypto_payments')) {
            Schema::create('crypto_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('listing_id')->nullable()->constrained('listings')->cascadeOnDelete();
                $table->foreignId('boost_plan_id')->nullable()->constrained('boost_plans')->cascadeOnDelete();
                $table->foreignId('gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
                $table->string('internal_order_id', 64)->unique();
                $table->string('provider_payment_id', 150)->nullable();
                $table->string('provider_invoice_id', 150)->nullable();
                $table->string('payment_currency', 20); // BTC, ETH, USDT, LTC, etc.
                $table->string('payment_network', 40);  // TRC20, ERC20, BEP20, Polygon, Solana, Native
                $table->decimal('expected_amount', 24, 8);
                $table->decimal('received_amount', 24, 8)->default(0.00000000);
                $table->decimal('fiat_amount', 10, 2);
                $table->string('fiat_currency', 3)->default('USD');
                $table->string('transaction_hash', 150)->nullable();
                $table->string('payment_address', 150)->nullable();
                $table->text('payment_url')->nullable();
                $table->text('qr_code_data')->nullable();
                $table->unsignedInteger('confirmations')->default(0);
                $table->unsignedInteger('required_confirmations')->default(1);
                $table->string('status', 40)->default('CREATED');
                $table->timestamp('expires_at');
                $table->timestamp('confirmed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['listing_id', 'status']);
                $table->index(['gateway_id', 'status']);
                $table->index('provider_payment_id');
                $table->index('provider_invoice_id');
                $table->index('internal_order_id');
                $table->index('status');
                $table->index('expires_at');
            });
        }

        // 4. Payment Webhook Events Table
        if (! Schema::hasTable('payment_webhook_events')) {
            Schema::create('payment_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
                $table->foreignId('crypto_payment_id')->nullable()->constrained('crypto_payments')->nullOnDelete();
                $table->string('provider_event_id', 150)->nullable();
                $table->string('event_type', 100);
                $table->json('payload');
                $table->json('headers')->nullable();
                $table->boolean('signature_verified')->default(false);
                $table->string('processing_status', 30)->default('pending'); // pending, processed, failed, ignored, duplicate
                $table->text('error_message')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['gateway_id', 'processing_status']);
                $table->index('provider_event_id');
                $table->index('crypto_payment_id');
            });
        }

        // 5. Listing Boosts Table
        if (! Schema::hasTable('listing_boosts')) {
            Schema::create('listing_boosts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('payment_id')->constrained('crypto_payments')->cascadeOnDelete();
                $table->foreignId('boost_plan_id')->constrained('boost_plans')->cascadeOnDelete();
                $table->timestamp('starts_at');
                $table->timestamp('expires_at');
                $table->string('status', 30)->default('active'); // active, expired, cancelled, revoked
                $table->timestamps();

                $table->index(['listing_id', 'status']);
                $table->index(['user_id', 'status']);
                $table->index(['status', 'expires_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_boosts');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('crypto_payments');
        Schema::dropIfExists('boost_plans');
        Schema::dropIfExists('payment_gateways');
    }
};

