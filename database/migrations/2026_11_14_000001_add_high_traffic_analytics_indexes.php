<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite indexes for high-traffic analytics deduplication and bot filtering.
     */
    public function up(): void
    {
        if (Schema::hasTable('platform_traffic_logs')) {
            Schema::table('platform_traffic_logs', function (Blueprint $table) {
                $table->index(['visitor_hash', 'path', 'created_at'], 'ptl_hash_path_created_idx');
                $table->index(['created_at', 'is_bot'], 'ptl_created_bot_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('platform_traffic_logs')) {
            Schema::table('platform_traffic_logs', function (Blueprint $table) {
                $table->dropIndex('ptl_hash_path_created_idx');
                $table->dropIndex('ptl_created_bot_idx');
            });
        }
    }
};

