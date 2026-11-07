<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 19: Site Advertisement Placements (house / sponsor banner slots managed by admins)
     */
    public function up(): void
    {
        if (Schema::hasTable('ad_placements')) {
            return;
        }

        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slot', 50); // homepage_top, sidebar, search_results, listing_detail, footer
            $table->string('advertiser_name', 120)->nullable();
            $table->string('image_url')->nullable();
            $table->string('target_url')->nullable();
            $table->text('html_snippet')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['slot', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_placements');
    }
};

