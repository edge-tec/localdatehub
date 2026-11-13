<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blockchain_networks') && !Schema::hasColumn('blockchain_networks', 'is_testnet')) {
            Schema::table('blockchain_networks', function (Blueprint $table) {
                $table->boolean('is_testnet')->default(false)->after('network_type')->index();
            });
        }

        if (Schema::hasTable('treasury_wallets') && !Schema::hasColumn('treasury_wallets', 'is_testnet')) {
            Schema::table('treasury_wallets', function (Blueprint $table) {
                $table->boolean('is_testnet')->default(false)->after('is_allowlisted')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('treasury_wallets') && Schema::hasColumn('treasury_wallets', 'is_testnet')) {
            Schema::table('treasury_wallets', function (Blueprint $table) {
                $table->dropColumn('is_testnet');
            });
        }

        if (Schema::hasTable('blockchain_networks') && Schema::hasColumn('blockchain_networks', 'is_testnet')) {
            Schema::table('blockchain_networks', function (Blueprint $table) {
                $table->dropColumn('is_testnet');
            });
        }
    }
};

