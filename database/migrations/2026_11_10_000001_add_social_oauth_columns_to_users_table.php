<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 150)->nullable()->index()->after('email');
            $table->string('oauth_provider', 30)->nullable()->after('google_id');
            $table->string('oauth_id', 150)->nullable()->index()->after('oauth_provider');
            $table->string('avatar', 500)->nullable()->after('oauth_id');
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['google_id']);
            $table->dropIndex(['oauth_id']);
            $table->dropColumn(['google_id', 'oauth_provider', 'oauth_id', 'avatar']);
        });
    }
};
