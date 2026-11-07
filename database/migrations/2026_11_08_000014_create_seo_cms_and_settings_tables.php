<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 14: SEO Metas, CMS Static Pages & System Settings
     */
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->string('page_route', 120)->nullable()->unique();
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('meta_title', 150);
            $table->text('meta_description');
            $table->string('meta_keywords', 255)->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image_url')->nullable();
            $table->string('robots_directive', 50)->default('index, follow');
            $table->json('structured_data')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('page_route');
        });

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique(); // about, terms, privacy, safety, anti-trafficking, contact
            $table->string('title', 150);
            $table->longText('content');
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('is_published');
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->longText('value')->nullable();
            $table->string('group', 40)->default('general'); // general, safety, monetization, locations, contact
            $table->string('type', 20)->default('string'); // string, boolean, integer, json
            $table->string('description', 255)->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('seo_metas');
    }
};
