<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 28: Real-Time Platform Traffic & Visitor Geo-Tracking
     */
    public function up(): void
    {
        if (! Schema::hasTable('platform_traffic_logs')) {
            Schema::create('platform_traffic_logs', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->nullable();
                $table->string('visitor_hash', 64)->index();
                $table->string('country_code', 2)->default('US')->index();
                $table->string('country_name', 100)->default('United States');
                $table->string('city_name', 100)->nullable();
                $table->string('state_code', 10)->nullable();
                $table->string('path', 255);
                $table->string('method', 10)->default('GET');
                $table->string('referrer', 255)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->string('device_type', 20)->default('Desktop'); // Desktop, Mobile, Tablet
                $table->string('browser', 40)->default('Chrome');
                $table->string('os', 40)->default('Windows');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('session_id', 80)->nullable();
                $table->boolean('is_bot')->default(false);
                $table->unsignedInteger('response_time_ms')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();

                $table->index(['country_code', 'created_at']);
                $table->index(['path', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_traffic_logs');
    }
};

