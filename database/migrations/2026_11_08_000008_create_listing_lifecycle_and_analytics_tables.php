<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 8: Listing Revisions, Status History, Favorites & Analytics
     */
    public function up(): void
    {
        Schema::create('listing_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description');
            $table->json('attributes_snapshot')->nullable();
            $table->string('change_summary', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['listing_id', 'created_at']);
        });

        Schema::create('listing_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->string('from_status', 30);
            $table->string('to_status', 30);
            $table->string('reason', 255)->nullable();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['listing_id', 'created_at']);
        });

        Schema::create('listing_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'listing_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('listing_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->date('metric_date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('contact_clicks')->default(0);
            $table->unsignedInteger('favorite_adds')->default(0);
            $table->timestamps();

            $table->unique(['listing_id', 'metric_date']);
            $table->index('metric_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_analytics');
        Schema::dropIfExists('listing_favorites');
        Schema::dropIfExists('listing_status_history');
        Schema::dropIfExists('listing_revisions');
    }
};
