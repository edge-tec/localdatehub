<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 17: Enhance Listings for Duplicate Detection and Moderation Workflow
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->after('description');
            $table->string('rejection_reason', 255)->nullable()->after('status');
            $table->unsignedTinyInteger('rejection_count')->default(0)->after('rejection_reason');
            $table->boolean('is_autosaved')->default(false)->after('rejection_count');

            $table->index('content_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['content_hash']);
            $table->dropColumn(['content_hash', 'rejection_reason', 'rejection_count', 'is_autosaved']);
        });
    }
};

