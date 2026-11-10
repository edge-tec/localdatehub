<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listing_contacts')) {
            Schema::table('listing_contacts', function (Blueprint $table) {
                if (!Schema::hasColumn('listing_contacts', 'show_email')) {
                    $table->boolean('show_email')->default(false)->after('allow_email_relay');
                }
                if (!Schema::hasColumn('listing_contacts', 'preferred_email_encrypted')) {
                    $table->text('preferred_email_encrypted')->nullable()->after('masked_relay_email');
                }
            });
        }

        if (Schema::hasTable('user_preferences')) {
            Schema::table('user_preferences', function (Blueprint $table) {
                if (!Schema::hasColumn('user_preferences', 'show_public_email')) {
                    $table->boolean('show_public_email')->default(false)->after('allow_contact_messages');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('listing_contacts')) {
            Schema::table('listing_contacts', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('listing_contacts', 'show_email')) {
                    $cols[] = 'show_email';
                }
                if (Schema::hasColumn('listing_contacts', 'preferred_email_encrypted')) {
                    $cols[] = 'preferred_email_encrypted';
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('user_preferences') && Schema::hasColumn('user_preferences', 'show_public_email')) {
            Schema::table('user_preferences', function (Blueprint $table) {
                $table->dropColumn('show_public_email');
            });
        }
    }
};
