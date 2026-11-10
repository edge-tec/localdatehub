<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications_log')) {
            Schema::table('notifications_log', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications_log', 'message')) {
                    $table->text('message')->nullable()->after('subject');
                }
                if (!Schema::hasColumn('notifications_log', 'is_read')) {
                    $table->boolean('is_read')->default(false)->after('status');
                }
                if (!Schema::hasColumn('notifications_log', 'read_at')) {
                    $table->timestamp('read_at')->nullable()->after('is_read');
                }
            });
        }

        if (Schema::hasTable('content_reports')) {
            Schema::table('content_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('content_reports', 'is_read')) {
                    $table->boolean('is_read')->default(false)->after('status');
                }
                if (!Schema::hasColumn('content_reports', 'read_at')) {
                    $table->timestamp('read_at')->nullable()->after('is_read');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notifications_log')) {
            Schema::table('notifications_log', function (Blueprint $table) {
                $cols = array_filter(['message', 'is_read', 'read_at'], fn ($c) => Schema::hasColumn('notifications_log', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('content_reports')) {
            Schema::table('content_reports', function (Blueprint $table) {
                $cols = array_filter(['is_read', 'read_at'], fn ($c) => Schema::hasColumn('content_reports', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};

