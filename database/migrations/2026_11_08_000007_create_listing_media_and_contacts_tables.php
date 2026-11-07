<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 7: Listing Media, Contacts & Dynamic Attributes
     */
    public function up(): void
    {
        Schema::create('listing_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 50)->default('image/webp');
            $table->unsignedInteger('file_size_bytes')->default(0);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->string('moderation_status', 30)->default('approved'); // pending, approved, rejected, flagged
            $table->string('rejection_reason', 150)->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'is_primary']);
            $table->index('moderation_status');
        });

        Schema::create('listing_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained('listings')->cascadeOnDelete();
            $table->boolean('allow_platform_messages')->default(true);
            $table->boolean('allow_email_relay')->default(true);
            $table->string('masked_relay_email', 100)->nullable();
            $table->text('preferred_phone_encrypted')->nullable();
            $table->text('external_handle_encrypted')->nullable(); // e.g. Signal, Telegram, etc.
            $table->boolean('show_phone')->default(false);
            $table->string('preferred_contact_method', 30)->default('platform_message');
            $table->text('contact_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('listing_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('category_attribute_id')->constrained('category_attributes')->cascadeOnDelete();
            $table->foreignId('category_attribute_option_id')->nullable()->constrained('category_attribute_options')->nullOnDelete();
            $table->string('attribute_code', 50);
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 12, 4)->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'attribute_code']);
            $table->index(['attribute_code', 'value_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_attributes');
        Schema::dropIfExists('listing_contacts');
        Schema::dropIfExists('listing_media');
    }
};
