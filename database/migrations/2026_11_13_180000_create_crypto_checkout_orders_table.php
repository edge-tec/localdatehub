<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_checkout_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_reference', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('network_id')->constrained('blockchain_networks');
            $table->foreignId('asset_id')->constrained('crypto_assets');
            $table->foreignId('wallet_address_id')->constrained('wallet_addresses');
            $table->foreignId('deposit_id')->nullable()->unique()->constrained('deposits')->nullOnDelete();
            $table->string('title', 160);
            $table->string('expected_amount_base_units', 78);
            $table->string('received_amount_base_units', 78)->default('0');
            $table->string('status', 32)->default('PENDING')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_checkout_orders');
    }
};
