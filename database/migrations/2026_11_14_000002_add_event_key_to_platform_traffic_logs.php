<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add database-level idempotency unique key to platform_traffic_logs.
     */
    public function up(): void
    {
        if (Schema::hasTable('platform_traffic_logs') && !Schema::hasColumn('platform_traffic_logs', 'event_key')) {
            Schema::table('platform_traffic_logs', function (Blueprint $table) {
                $table->string('event_key', 64)->nullable()->unique('ptl_event_key_unique')->after('visitor_hash');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('platform_traffic_logs') && Schema::hasColumn('platform_traffic_logs', 'event_key')) {
            Schema::table('platform_traffic_logs', function (Blueprint $table) {
                $table->dropUnique('ptl_event_key_unique');
                $table->dropColumn('event_key');
            });
        }
    }
};

