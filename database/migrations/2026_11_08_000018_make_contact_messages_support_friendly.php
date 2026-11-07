<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 18: Allow Contact Messages to support both Listing Inquiries and General Platform Support
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('listing_id')->nullable()->change();
            $table->foreignId('recipient_id')->nullable()->change();
            $table->string('inquiry_type', 40)->default('listing_inquiry')->after('recipient_id');

            $table->index('inquiry_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['inquiry_type']);
            $table->dropColumn('inquiry_type');
        });
    }
};
