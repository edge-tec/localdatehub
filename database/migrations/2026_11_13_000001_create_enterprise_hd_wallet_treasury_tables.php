<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('blockchain_networks')) {
            Schema::create('blockchain_networks', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('network_code', 32)->unique();
                $table->unsignedBigInteger('chain_id')->nullable()->index();
                $table->string('network_type', 32)->index(); // evm, utxo, tron, solana, xrp
                $table->string('rpc_configuration_reference', 120)->nullable();
                $table->unsignedInteger('confirmation_threshold')->default(6);
                $table->boolean('is_enabled')->default(false)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('crypto_assets')) {
            Schema::create('crypto_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('network_id')->constrained('blockchain_networks')->cascadeOnDelete();
                $table->string('symbol', 24);
                $table->string('name', 100);
                $table->string('contract_address', 128)->nullable();
                $table->unsignedTinyInteger('decimals')->default(18);
                $table->string('asset_type', 32)->default('native'); // native, erc20, bep20, trc20, spl
                $table->string('minimum_deposit', 64)->default('0'); // integer base units string
                $table->boolean('is_enabled')->default(true)->index();
                $table->timestamps();

                $table->unique(['network_id', 'symbol'], 'crypto_assets_network_symbol_unique');
                $table->index(['network_id', 'contract_address'], 'crypto_assets_network_contract_idx');
            });
        }

        if (!Schema::hasTable('wallet_accounts')) {
            Schema::create('wallet_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('account_reference', 64)->unique();
                $table->string('account_status', 32)->default('ACTIVE')->index();
                $table->timestamps();

                $table->unique(['user_id', 'account_reference'], 'wallet_accounts_user_ref_unique');
            });
        }

        if (!Schema::hasTable('wallet_addresses')) {
            Schema::create('wallet_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('network_id')->constrained('blockchain_networks')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('crypto_assets')->cascadeOnDelete();
                $table->foreignId('wallet_account_id')->constrained('wallet_accounts')->cascadeOnDelete();
                $table->string('address', 128);
                $table->string('derivation_path', 128);
                $table->unsignedBigInteger('derivation_index');
                $table->string('address_type', 32)->default('deposit'); // p2wpkh, evm, trc20, spl, xrp_tag
                $table->string('status', 32)->default('ACTIVE')->index();
                $table->timestamps();

                $table->unique(['network_id', 'address'], 'wallet_addresses_network_addr_unique');
                $table->unique(['user_id', 'network_id', 'asset_id'], 'wallet_addresses_user_net_asset_unique');
                $table->unique(['network_id', 'derivation_index'], 'wallet_addresses_net_index_unique');
                $table->index(['address', 'status'], 'wallet_addresses_addr_status_idx');
            });
        }

        if (!Schema::hasTable('blockchain_checkpoints')) {
            Schema::create('blockchain_checkpoints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('network_id')->unique()->constrained('blockchain_networks')->cascadeOnDelete();
                $table->unsignedBigInteger('last_processed_height')->default(0);
                $table->string('last_processed_hash', 128)->nullable();
                $table->timestamp('last_processed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('blockchain_transactions')) {
            Schema::create('blockchain_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('network_id')->constrained('blockchain_networks')->cascadeOnDelete();
                $table->string('transaction_hash', 128);
                $table->unsignedInteger('transaction_index')->default(0);
                $table->unsignedBigInteger('block_height')->nullable()->index();
                $table->string('block_hash', 128)->nullable();
                $table->string('sender_address', 128)->nullable();
                $table->string('recipient_address', 128)->index();
                $table->foreignId('asset_id')->constrained('crypto_assets')->cascadeOnDelete();
                $table->string('amount_base_units', 64);
                $table->unsignedInteger('confirmation_count')->default(0);
                $table->string('transaction_status', 32)->default('DETECTED')->index();
                $table->string('raw_transaction_reference', 255)->nullable();
                $table->timestamp('detected_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->unique(['network_id', 'transaction_hash', 'recipient_address', 'asset_id'], 'btx_net_hash_recip_asset_unique');
                $table->index(['network_id', 'block_height'], 'btx_net_height_idx');
            });
        }

        if (!Schema::hasTable('deposits')) {
            Schema::create('deposits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('wallet_address_id')->constrained('wallet_addresses')->cascadeOnDelete();
                $table->foreignId('blockchain_transaction_id')->unique()->constrained('blockchain_transactions')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('crypto_assets')->cascadeOnDelete();
                $table->foreignId('network_id')->constrained('blockchain_networks')->cascadeOnDelete();
                $table->string('amount_base_units', 64);
                $table->string('credited_amount_base_units', 64)->default('0');
                $table->string('status', 32)->default('DETECTED')->index();
                $table->timestamp('credited_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status'], 'deposits_user_status_idx');
                $table->index(['network_id', 'status'], 'deposits_network_status_idx');
            });
        }

        if (!Schema::hasTable('ledger_entries')) {
            Schema::create('ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('deposit_id')->nullable()->constrained('deposits')->nullOnDelete();
                $table->string('transaction_type', 40)->index();
                $table->string('direction', 16); // CREDIT, DEBIT
                $table->string('amount_base_units', 64);
                $table->foreignId('asset_id')->constrained('crypto_assets')->cascadeOnDelete();
                $table->string('reference', 80)->unique();
                $table->string('idempotency_key', 120)->unique();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['user_id', 'asset_id'], 'ledger_entries_user_asset_idx');
            });
        }

        if (!Schema::hasTable('treasury_wallets')) {
            Schema::create('treasury_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('network_id')->constrained('blockchain_networks')->cascadeOnDelete();
                $table->foreignId('asset_id')->nullable()->constrained('crypto_assets')->nullOnDelete();
                $table->string('wallet_address', 128);
                $table->string('custody_provider', 40)->default('trust_wallet');
                $table->string('custody_account_reference', 120)->nullable();
                $table->boolean('is_allowlisted')->default(true);
                $table->string('status', 32)->default('ACTIVE')->index();
                $table->timestamps();

                $table->unique(['network_id', 'wallet_address'], 'treasury_wallets_net_addr_unique');
            });
        }

        if (!Schema::hasTable('sweep_transactions')) {
            Schema::create('sweep_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deposit_id')->unique()->constrained('deposits')->cascadeOnDelete();
                $table->foreignId('treasury_wallet_id')->constrained('treasury_wallets')->cascadeOnDelete();
                $table->foreignId('network_id')->constrained('blockchain_networks')->cascadeOnDelete();
                $table->string('transaction_hash', 128)->nullable()->index();
                $table->unsignedBigInteger('nonce')->nullable();
                $table->string('amount_base_units', 64);
                $table->string('network_fee_base_units', 64)->default('0');
                $table->string('status', 32)->default('SWEEP_QUEUED')->index();
                $table->unsignedInteger('attempt_count')->default(0);
                $table->string('last_error_code', 80)->nullable();
                $table->timestamp('broadcast_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();

                $table->index(['network_id', 'status'], 'sweep_tx_net_status_idx');
            });
        }

        if (!Schema::hasTable('gas_reserves')) {
            Schema::create('gas_reserves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('network_id')->constrained('blockchain_networks')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('crypto_assets')->cascadeOnDelete();
                $table->foreignId('treasury_wallet_id')->nullable()->constrained('treasury_wallets')->nullOnDelete();
                $table->string('available_balance_base_units', 64)->default('0');
                $table->string('minimum_reserve_base_units', 64)->default('0');
                $table->string('status', 32)->default('OPTIMAL')->index();
                $table->timestamp('updated_at')->nullable();

                $table->unique(['network_id', 'asset_id'], 'gas_reserves_net_asset_unique');
            });
        }

        if (!Schema::hasTable('webhook_events')) {
            Schema::create('webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 64);
                $table->string('event_id', 128);
                $table->foreignId('network_id')->nullable()->constrained('blockchain_networks')->nullOnDelete();
                $table->string('payload_reference', 255)->nullable();
                $table->string('verification_status', 32)->default('PENDING')->index();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['provider', 'event_id'], 'webhook_events_provider_event_unique');
            });
        }

        if (!Schema::hasTable('recovery_queue')) {
            Schema::create('recovery_queue', function (Blueprint $table) {
                $table->id();
                $table->string('operation_type', 48); // SWEEP, DEPOSIT_VERIFY, REORG_RECONCILE
                $table->unsignedBigInteger('reference_id');
                $table->string('failure_code', 80);
                $table->unsignedInteger('retry_count')->default(0);
                $table->timestamp('next_retry_at')->nullable()->index();
                $table->string('status', 32)->default('PENDING')->index();
                $table->timestamps();

                $table->unique(['operation_type', 'reference_id'], 'recovery_queue_op_ref_unique');
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 80)->index();
                $table->string('resource_type', 80)->index();
                $table->string('resource_id', 80)->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('recovery_queue');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('gas_reserves');
        Schema::dropIfExists('sweep_transactions');
        Schema::dropIfExists('treasury_wallets');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('blockchain_transactions');
        Schema::dropIfExists('blockchain_checkpoints');
        Schema::dropIfExists('wallet_addresses');
        Schema::dropIfExists('wallet_accounts');
        Schema::dropIfExists('crypto_assets');
        Schema::dropIfExists('blockchain_networks');
    }
};

