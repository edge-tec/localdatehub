<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 6: Personal Classified Listings and Listing Locations
     */
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('zip_code_id')->nullable()->constrained('zip_codes')->nullOnDelete();
            
            $table->string('title', 150);
            $table->string('slug', 180)->unique();
            $table->text('description');
            $table->unsignedSmallInteger('poster_age');
            $table->string('poster_gender', 30)->nullable();
            $table->string('intent', 50)->default('dating'); // dating, friendship, activity, serious, casual_coffee
            
            // Lifecycle & Status
            $table->string('status', 30)->default('pending_review'); // draft, pending_review, active, paused, expired, rejected, suspended, archived
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified_poster')->default(false);
            $table->boolean('has_photos')->default(false);
            
            // Analytics counters
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('reply_count')->default(0);
            $table->unsignedInteger('favorite_count')->default(0);

            // Timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('renewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // High-traffic composite indexes
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['state_id', 'city_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('is_featured');
            $table->index('expires_at');
        });

        Schema::create('listing_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained('listings')->cascadeOnDelete();
            $table->string('neighborhood', 100)->nullable();
            $table->string('cross_streets', 150)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('fuzzed_latitude', 10, 7);
            $table->decimal('fuzzed_longitude', 10, 7);
            $table->boolean('hide_exact_distance')->default(false);
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index(['fuzzed_latitude', 'fuzzed_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_locations');
        Schema::dropIfExists('listings');
    }
};
