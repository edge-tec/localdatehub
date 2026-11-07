<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 1: Users & Accounts
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->unique()->after('email');
            $table->string('role', 30)->default('user')->after('password'); // user, advertiser, moderator, admin
            $table->string('account_status', 30)->default('active')->after('role'); // active, suspended, pending_verification, closed
            $table->boolean('is_age_verified')->default(false)->after('account_status');
            $table->boolean('is_identity_verified')->default(false)->after('is_age_verified');
            $table->boolean('two_factor_enabled')->default(false)->after('is_identity_verified');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->timestamp('last_active_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_active_at');
            $table->softDeletes()->after('updated_at');

            $table->index(['account_status', 'is_age_verified']);
            $table->index('last_active_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [
                'username',
                'phone',
                'role',
                'account_status',
                'is_age_verified',
                'is_identity_verified',
                'two_factor_enabled',
                'two_factor_secret',
                'phone_verified_at',
                'last_active_at',
                'last_login_ip',
                'deleted_at',
            ];

            $existing = array_filter($columnsToDrop, fn ($col) => Schema::hasColumn('users', $col));

            if (!empty($existing)) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};
