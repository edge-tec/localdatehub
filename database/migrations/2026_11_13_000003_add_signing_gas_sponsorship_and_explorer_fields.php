<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('signing_requests')) {
            Schema::create('signing_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sweep_transaction_id')->nullable()->constrained('sweep_transactions')->nullOnDelete();
                $table->foreignId('network_id')->constrained('blockchain_networks')->cascadeOnDelete();
                $table->string('request_uuid', 64)->unique();
                $table->string('intent_type', 32)->default('SWEEP'); // SWEEP, GAS_SPONSOR
                $table->string('from_address', 128)->nullable();
                $table->string('to_address', 128);
                $table->string('amount_base_units', 64);
                $table->unsignedBigInteger('nonce')->nullable();
                $table->string('custody_provider', 40);
                $table->string('status', 32)->default('SIGNED')->index();
                $table->string('error_code', 80)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('gas_sponsorship_transactions')) {
            Schema::create('gas_sponsorship_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('network_id')->constrained('blockchain_networks')->cascadeOnDelete();
                $table->foreignId('deposit_id')->constrained('deposits')->cascadeOnDelete();
                $table->string('target_address', 128)->index();
                $table->string('amount_base_units', 64);
                $table->string('transaction_hash', 128)->nullable()->index();
                $table->string('status', 32)->default('BROADCAST')->index(); // BROADCAST, CONFIRMED, FAILED
                $table->string('error_code', 80)->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('blockchain_networks') && !Schema::hasColumn('blockchain_networks', 'explorer_base_url')) {
            Schema::table('blockchain_networks', function (Blueprint $table) {
                $table->string('explorer_base_url', 255)->nullable()->after('rpc_configuration_reference');
            });
        }

        if (Schema::hasTable('sweep_transactions') && !Schema::hasColumn('sweep_transactions', 'gas_sponsored_base_units')) {
            Schema::table('sweep_transactions', function (Blueprint $table) {
                $table->string('gas_sponsored_base_units', 64)->default('0')->after('network_fee_base_units');
                $table->string('explorer_url', 255)->nullable()->after('transaction_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sweep_transactions')) {
            Schema::table('sweep_transactions', function (Blueprint $table) {
                $cols = array_filter(['gas_sponsored_base_units', 'explorer_url'], fn ($c) => Schema::hasColumn('sweep_transactions', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('blockchain_networks') && Schema::hasColumn('blockchain_networks', 'explorer_base_url')) {
            Schema::table('blockchain_networks', function (Blueprint $table) {
                $table->dropColumn('explorer_base_url');
            });
        }

        Schema::dropIfExists('gas_sponsorship_transactions');
        Schema::dropIfExists('signing_requests');
    }
};

