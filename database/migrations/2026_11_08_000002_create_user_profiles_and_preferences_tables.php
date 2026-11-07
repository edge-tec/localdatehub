<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 2: User Profiles and Preferences
     */
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('display_name', 100)->nullable();
            $table->text('bio')->nullable();
            $table->string('gender', 50)->nullable();
            $table->string('seeking', 100)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state_code', 10)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->string('avatar_path')->nullable();
            $table->json('social_handles')->nullable();
            $table->boolean('hide_location_details')->default(false);
            $table->boolean('hide_age')->default(false);
            $table->timestamps();

            $table->index(['state_code', 'city']);
            $table->index('zip_code');
        });

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('notification_settings')->nullable();
            $table->json('privacy_settings')->nullable();
            $table->json('search_defaults')->nullable();
            $table->boolean('allow_contact_messages')->default(true);
            $table->boolean('require_verified_senders')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('user_profiles');
    }
};
