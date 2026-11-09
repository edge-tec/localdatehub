<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 27: Enterprise In-House Crypto Gateway, Hybrid Treasury & Gas Reserve Management
     */
    public function up(): void
    {
        // 1. Crypto Currencies
        if (! Schema::hasTable('crypto_currencies')) {
            Schema::create('crypto_currencies', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50);
                $table->string('symbol', 20)->unique();
                $table->boolean('is_active')->default(true);
                $table->unsignedTinyInteger('decimal_precision')->default(6);
                $table->decimal('min_deposit_usd', 10, 2)->default(5.00);
                $table->decimal('max_deposit_usd', 10, 2)->default(10000.00);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }

        // 2. Crypto Networks
        if (! Schema::hasTable('crypto_networks')) {
            Schema::create('crypto_networks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('currency_id')->constrained('crypto_currencies')->cascadeOnDelete();
                $table->string('network_key', 30); // TRC20, ERC20, BEP20, BTC, ETH, BNB, TRX
                $table->string('chain_name', 60);
                $table->unsignedBigInteger('chain_id')->nullable();
                $table->string('contract_address', 120)->nullable(); // null for native coins
                $table->string('gas_currency', 20); // TRX, ETH, BNB, BTC
                $table->unsignedSmallInteger('required_confirmations')->default(1);
                $table->boolean('is_testnet')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('rpc_endpoint_primary', 255);
                $table->string('rpc_endpoint_fallback', 255)->nullable();
                $table->string('explorer_tx_url', 255)->nullable();
                $table->string('explorer_address_url', 255)->nullable();
                $table->decimal('network_fee_estimate_usd', 10, 2)->default(1.00);
                $table->unsignedSmallInteger('payment_timeout_minutes')->default(30);
                $table->timestamps();

                $table->unique(['currency_id', 'network_key', 'is_testnet']);
                $table->index(['network_key', 'is_active']);
            });
        }

        // 3. Crypto Payment Settings
        if (! Schema::hasTable('crypto_payment_settings')) {
            Schema::create('crypto_payment_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('description', 255)->nullable();
                $table->boolean('is_encrypted')->default(false);
                $table->timestamps();
            });
        }

        // 4. Crypto Exchange Rates
        if (! Schema::hasTable('crypto_exchange_rates')) {
            Schema::create('crypto_exchange_rates', function (Blueprint $table) {
                $table->id();
                $table->string('currency_symbol', 20);
                $table->string('fiat_currency', 10)->default('USD');
                $table->decimal('rate', 18, 8);
                $table->string('rate_source', 60)->default('coingecko');
                $table->decimal('markup_percentage', 5, 2)->default(0.00);
                $table->boolean('is_manual_override')->default(false);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['currency_symbol', 'fiat_currency']);
            });
        }

        // 5. Crypto Treasury Wallets (Trust Wallet Architecture)
        if (! Schema::hasTable('crypto_treasury_wallets')) {
            Schema::create('crypto_treasury_wallets', function (Blueprint $table) {
                $table->id();
                $table->string('wallet_name', 100);
                $table->string('tier', 40); // temporary_trust_wallet, main_trust_wallet
                $table->string('network_key', 30);
                $table->string('public_receiving_address', 150);
                $table->decimal('current_balance', 24, 8)->default(0);
                $table->decimal('available_balance', 24, 8)->default(0);
                $table->decimal('pending_deposits', 24, 8)->default(0);
                $table->decimal('total_received', 24, 8)->default(0);
                $table->decimal('total_transferred', 24, 8)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['tier', 'network_key', 'is_active'], 'ctw_tier_net_active_idx');
                $table->unique(['tier', 'network_key', 'public_receiving_address'], 'ctw_tier_net_pubaddr_unique');
            });
        }

        // 6. Crypto Deposit Addresses (Unique HD Derivation)
        if (! Schema::hasTable('crypto_deposit_addresses')) {
            Schema::create('crypto_deposit_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('currency_id')->constrained('crypto_currencies')->cascadeOnDelete();
                $table->foreignId('network_id')->constrained('crypto_networks')->cascadeOnDelete();
                $table->string('public_address', 150);
                $table->unsignedBigInteger('derivation_index')->default(0);
                $table->string('derivation_path', 100)->nullable();
                $table->boolean('is_assigned')->default(false);
                $table->unsignedBigInteger('assigned_invoice_id')->nullable();
                $table->timestamp('last_detected_at')->nullable();
                $table->timestamps();

                $table->index(['public_address', 'is_assigned']);
                $table->index(['user_id', 'is_assigned']);
            });
        }

        // 7. Crypto Payment Invoices
        if (! Schema::hasTable('crypto_payment_invoices')) {
            Schema::create('crypto_payment_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number', 50)->unique(); // e.g. CRYPTO-INV-100105
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('deposit_address_id')->nullable()->constrained('crypto_deposit_addresses')->nullOnDelete();
                $table->string('currency_symbol', 20);
                $table->string('network_key', 30);
                $table->decimal('fiat_amount_usd', 10, 2);
                $table->decimal('expected_crypto_amount', 24, 8);
                $table->decimal('received_crypto_amount', 24, 8)->default(0);
                $table->decimal('exchange_rate_snapshot', 18, 8);
                $table->string('status', 30)->default('WAITING_FOR_PAYMENT');
                $table->unsignedSmallInteger('required_confirmations')->default(1);
                $table->unsignedSmallInteger('current_confirmations')->default(0);
                $table->string('tx_hash', 150)->nullable();
                $table->string('contract_address', 120)->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('detected_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('credited_at')->nullable();
                $table->unsignedBigInteger('credit_package_id')->nullable();
                $table->unsignedBigInteger('listing_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['status', 'expires_at']);
                $table->index(['user_id', 'status']);
                $table->index('tx_hash');
            });
        }

        // 8. Crypto Transactions
        if (! Schema::hasTable('crypto_transactions')) {
            Schema::create('crypto_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->nullable()->constrained('crypto_payment_invoices')->nullOnDelete();
                $table->foreignId('deposit_address_id')->nullable()->constrained('crypto_deposit_addresses')->nullOnDelete();
                $table->string('tx_hash', 150)->unique();
                $table->unsignedBigInteger('block_height')->nullable();
                $table->string('from_address', 150)->nullable();
                $table->string('to_address', 150);
                $table->string('token_contract', 120)->nullable();
                $table->decimal('amount', 24, 8);
                $table->unsignedSmallInteger('confirmations')->default(0);
                $table->boolean('is_valid')->default(false);
                $table->json('validation_errors')->nullable();
                $table->timestamp('detected_at');
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();

                $table->index(['to_address', 'is_valid']);
            });
        }

        // 9. Crypto Blockchain Blocks
        if (! Schema::hasTable('crypto_blockchain_blocks')) {
            Schema::create('crypto_blockchain_blocks', function (Blueprint $table) {
                $table->id();
                $table->string('network_key', 30)->unique();
                $table->unsignedBigInteger('last_scanned_block')->default(0);
                $table->unsignedBigInteger('highest_scanned_block')->default(0);
                $table->boolean('is_scanning')->default(false);
                $table->timestamp('last_scanned_at')->nullable();
                $table->timestamps();
            });
        }

        // 10. Crypto Payment Confirmations
        if (! Schema::hasTable('crypto_payment_confirmations')) {
            Schema::create('crypto_payment_confirmations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('crypto_transaction_id')->constrained('crypto_transactions')->cascadeOnDelete();
                $table->unsignedBigInteger('block_number');
                $table->string('block_hash', 150)->nullable();
                $table->unsignedSmallInteger('confirmations_count');
                $table->timestamp('verified_at');
                $table->timestamps();

                $table->index(['crypto_transaction_id', 'block_number'], 'cpc_tx_block_idx');
            });
        }

        // 11. Crypto Sweep Transactions (Hybrid Treasury Engine)
        if (! Schema::hasTable('crypto_sweep_transactions')) {
            Schema::create('crypto_sweep_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->nullable()->constrained('crypto_payment_invoices')->nullOnDelete();
                $table->string('source_address', 150);
                $table->string('destination_address', 150);
                $table->string('destination_treasury_tier', 40); // temporary_trust_wallet, main_trust_wallet
                $table->string('currency_symbol', 20);
                $table->string('network_key', 30);
                $table->decimal('amount', 24, 8);
                $table->decimal('amount_usd', 10, 2);
                $table->decimal('estimated_gas_cost', 18, 8)->default(0);
                $table->decimal('actual_gas_cost', 18, 8)->default(0);
                $table->string('gas_currency', 20);
                $table->string('gas_tx_hash', 150)->nullable();
                $table->string('sweep_tx_hash', 150)->nullable();
                $table->string('sweep_type', 30); // automatic_small, admin_approved_large, manual_admin
                $table->string('status', 30)->default('PENDING'); // PENDING, AWAITING_APPROVAL, SIGNING, BROADCASTED, CONFIRMED, FAILED, CANCELLED
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('executed_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['status', 'sweep_type']);
                $table->index(['network_key', 'status']);
            });
        }

        // 12. Crypto Sweep Approvals (Large Deposit Queue)
        if (! Schema::hasTable('crypto_sweep_approvals')) {
            Schema::create('crypto_sweep_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sweep_transaction_id')->constrained('crypto_sweep_transactions')->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('requested_amount_usd', 10, 2);
                $table->decimal('requested_crypto_amount', 24, 8);
                $table->string('network_key', 30);
                $table->string('destination_address', 150);
                $table->decimal('estimated_fee_usd', 10, 2)->default(0);
                $table->string('status', 20)->default('PENDING'); // PENDING, APPROVED, REJECTED
                $table->text('decision_reason')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'sweep_transaction_id']);
            });
        }

        // 13. Crypto Signing Requests
        if (! Schema::hasTable('crypto_signing_requests')) {
            Schema::create('crypto_signing_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sweep_transaction_id')->constrained('crypto_sweep_transactions')->cascadeOnDelete();
                $table->string('payload_hash', 150);
                $table->string('signing_status', 30)->default('PENDING'); // PENDING, SIGNED, REJECTED
                $table->text('signed_payload')->nullable();
                $table->timestamps();
            });
        }

        // 14. Crypto Internal Ledger (Immutable User Balance Records)
        if (! Schema::hasTable('crypto_internal_ledger')) {
            Schema::create('crypto_internal_ledger', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('crypto_payment_invoices')->nullOnDelete();
                $table->string('entry_type', 30); // DEPOSIT_CREDIT, MONETIZATION_SPEND, REFUND, ADMIN_ADJUSTMENT
                $table->decimal('amount_usd', 10, 2);
                $table->decimal('balance_before_usd', 10, 2);
                $table->decimal('balance_after_usd', 10, 2);
                $table->string('reference_code', 60)->unique();
                $table->string('idempotency_key', 100)->unique()->nullable();
                $table->text('audit_notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'entry_type']);
                $table->index('reference_code');
            });
        }

        // 15. Crypto Reconciliation Records
        if (! Schema::hasTable('crypto_reconciliation_records')) {
            Schema::create('crypto_reconciliation_records', function (Blueprint $table) {
                $table->id();
                $table->date('reconciliation_date')->unique();
                $table->decimal('total_deposits_detected', 14, 2)->default(0);
                $table->decimal('total_credits_granted', 14, 2)->default(0);
                $table->decimal('total_funds_swept', 14, 2)->default(0);
                $table->decimal('total_network_fees_paid', 14, 2)->default(0);
                $table->unsignedInteger('discrepancies_count')->default(0);
                $table->json('summary_json')->nullable();
                $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reconciled_at')->nullable();
                $table->timestamps();
            });
        }

        // 16. Crypto Gas Wallets (Phase 14.5)
        if (! Schema::hasTable('crypto_gas_wallets')) {
            Schema::create('crypto_gas_wallets', function (Blueprint $table) {
                $table->id();
                $table->string('network_key', 30);
                $table->string('gas_currency', 20); // TRX, ETH, BNB, BTC
                $table->string('public_address', 150);
                $table->decimal('balance_native', 24, 8)->default(0);
                $table->decimal('balance_usd', 10, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['network_key', 'public_address']);
            });
        }

        // 17. Crypto Gas Reserves (Phase 14.5)
        if (! Schema::hasTable('crypto_gas_reserves')) {
            Schema::create('crypto_gas_reserves', function (Blueprint $table) {
                $table->id();
                $table->string('network_key', 30)->unique();
                $table->string('gas_currency', 20); // TRX, ETH, BNB, BTC
                $table->decimal('current_balance', 24, 8)->default(0);
                $table->decimal('min_reserve_threshold', 24, 8)->default(10.0);
                $table->decimal('emergency_reserve_threshold', 24, 8)->default(5.0);
                $table->decimal('target_reserve', 24, 8)->default(50.0);
                $table->decimal('replenishment_threshold', 24, 8)->default(15.0);
                $table->decimal('daily_spending_limit', 24, 8)->default(100.0);
                $table->decimal('spent_today', 24, 8)->default(0);
                $table->boolean('is_alert_active')->default(false);
                $table->timestamp('last_replenished_at')->nullable();
                $table->timestamps();
            });
        }

        // 18. Crypto Gas Transactions (Phase 14.5)
        if (! Schema::hasTable('crypto_gas_transactions')) {
            Schema::create('crypto_gas_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gas_wallet_id')->nullable()->constrained('crypto_gas_wallets')->nullOnDelete();
                $table->foreignId('sweep_transaction_id')->nullable()->constrained('crypto_sweep_transactions')->nullOnDelete();
                $table->string('network_key', 30);
                $table->string('gas_currency', 20);
                $table->decimal('fee_amount', 24, 8);
                $table->decimal('fee_usd', 10, 2)->default(0);
                $table->string('tx_hash', 150)->nullable();
                $table->string('status', 20)->default('CONFIRMED');
                $table->timestamps();

                $table->index(['network_key', 'status']);
            });
        }

        // 19. Crypto Sweep Cost Estimates (Phase 14.5)
        if (! Schema::hasTable('crypto_sweep_cost_estimates')) {
            Schema::create('crypto_sweep_cost_estimates', function (Blueprint $table) {
                $table->id();
                $table->string('network_key', 30);
                $table->string('currency_symbol', 20);
                $table->decimal('token_balance', 24, 8);
                $table->unsignedBigInteger('estimated_gas_units')->default(0);
                $table->string('estimated_gas_price', 60)->default('0');
                $table->decimal('estimated_fee_native', 24, 8);
                $table->decimal('estimated_fee_usd', 10, 2);
                $table->decimal('net_transfer_crypto', 24, 8);
                $table->boolean('is_profitable')->default(true);
                $table->boolean('passes_admin_threshold')->default(true);
                $table->timestamp('calculated_at');
                $table->timestamps();

                $table->index(['network_key', 'currency_symbol'], 'csce_net_curr_idx');
            });
        }

        // 20. Crypto Sweep Fee Records (Phase 14.5)
        if (! Schema::hasTable('crypto_sweep_fee_records')) {
            Schema::create('crypto_sweep_fee_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sweep_transaction_id')->constrained('crypto_sweep_transactions')->cascadeOnDelete();
                $table->decimal('estimated_fee_native', 24, 8);
                $table->decimal('actual_fee_native', 24, 8);
                $table->decimal('variance_native', 24, 8)->default(0);
                $table->decimal('estimated_fee_usd', 10, 2);
                $table->decimal('actual_fee_usd', 10, 2);
                $table->timestamp('reconciled_at');
                $table->timestamps();
            });
        }

        // 21. Crypto Gas Alerts (Phase 14.5)
        if (! Schema::hasTable('crypto_gas_alerts')) {
            Schema::create('crypto_gas_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('network_key', 30);
                $table->string('gas_currency', 20);
                $table->string('alert_level', 20); // WARNING, CRITICAL, EMERGENCY
                $table->text('message');
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['network_key', 'is_resolved']);
            });
        }

        // 22. Crypto Gas Funding Requests (Phase 14.5)
        if (! Schema::hasTable('crypto_gas_funding_requests')) {
            Schema::create('crypto_gas_funding_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gas_wallet_id')->constrained('crypto_gas_wallets')->cascadeOnDelete();
                $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('requested_amount_native', 24, 8);
                $table->string('network_key', 30);
                $table->string('status', 20)->default('PENDING'); // PENDING, APPROVED, EXECUTED, REJECTED
                $table->string('funding_tx_hash', 150)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['network_key', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_gas_funding_requests');
        Schema::dropIfExists('crypto_gas_alerts');
        Schema::dropIfExists('crypto_sweep_fee_records');
        Schema::dropIfExists('crypto_sweep_cost_estimates');
        Schema::dropIfExists('crypto_gas_transactions');
        Schema::dropIfExists('crypto_gas_reserves');
        Schema::dropIfExists('crypto_gas_wallets');
        Schema::dropIfExists('crypto_reconciliation_records');
        Schema::dropIfExists('crypto_internal_ledger');
        Schema::dropIfExists('crypto_signing_requests');
        Schema::dropIfExists('crypto_sweep_approvals');
        Schema::dropIfExists('crypto_sweep_transactions');
        Schema::dropIfExists('crypto_payment_confirmations');
        Schema::dropIfExists('crypto_blockchain_blocks');
        Schema::dropIfExists('crypto_transactions');
        Schema::dropIfExists('crypto_payment_invoices');
        Schema::dropIfExists('crypto_deposit_addresses');
        Schema::dropIfExists('crypto_treasury_wallets');
        Schema::dropIfExists('crypto_exchange_rates');
        Schema::dropIfExists('crypto_payment_settings');
        Schema::dropIfExists('crypto_networks');
        Schema::dropIfExists('crypto_currencies');
    }
};

