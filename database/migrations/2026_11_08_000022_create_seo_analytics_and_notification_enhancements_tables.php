<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 22: SEO, Search Trends, Privacy-Conscious Analytics & Notification Delivery
     */
    public function up(): void
    {
        // 1. Search Trends & Popular Query Aggregation
        if (! Schema::hasTable('search_trends')) {
            Schema::create('search_trends', function (Blueprint $table) {
                $table->id();
                $table->string('query', 150);
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
                $table->unsignedInteger('results_count')->default(0);
                $table->unsignedInteger('hits')->default(1);
                $table->timestamp('last_searched_at')->useCurrent();
                $table->timestamps();

                $table->index(['query', 'results_count']);
                $table->index(['category_id', 'hits']);
                $table->index('last_searched_at');
            });
        }

        // 2. Privacy-Conscious Platform Analytics Events (Salted Hashes Only)
        if (! Schema::hasTable('analytics_events')) {
            Schema::create('analytics_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 50); // listing_view, contact_click, favorite_add, search_executed, promotion_converted
                $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
                $table->string('visitor_hash', 64)->nullable(); // SHA-256 salted IP + UA; no PII stored
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['event_type', 'created_at']);
                $table->index(['listing_id', 'event_type']);
                $table->index(['state_id', 'city_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('search_trends');
    }
};
