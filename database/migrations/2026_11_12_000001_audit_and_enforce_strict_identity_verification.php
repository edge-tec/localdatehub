<?php

use App\Modules\Verification\Services\IdentityVerificationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'identity_verified_at')) {
                    $table->timestamp('identity_verified_at')->nullable()->after('is_identity_verified');
                }
                if (!Schema::hasColumn('users', 'verified_by_verification_id')) {
                    $table->unsignedBigInteger('verified_by_verification_id')
                        ->nullable()
                        ->after('identity_verified_at')
                        ->index();
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasTable('identity_verifications')) {
            app(IdentityVerificationService::class)->auditAllAccounts(true);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'verified_by_verification_id')) {
                    $table->dropIndex(['verified_by_verification_id']);
                    $table->dropColumn('verified_by_verification_id');
                }
                if (Schema::hasColumn('users', 'identity_verified_at')) {
                    $table->dropColumn('identity_verified_at');
                }
            });
        }
    }
};
