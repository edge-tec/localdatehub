<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 5: Dynamic Categories & Category Attributes
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('code', 20)->nullable(); // M4W, W4M, M4M, W4W, etc.
            $table->text('description')->nullable();
            $table->string('icon', 50)->default('heart');
            $table->unsignedSmallInteger('min_age_override')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('allowed_contact_methods')->nullable();
            $table->json('seo_metadata')->nullable();
            $table->timestamps();

            $table->index(['parent_id', 'display_order']);
            $table->index('is_active');
            $table->index('is_featured');
        });

        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete(); // null = global across all categories
            $table->string('name', 100);
            $table->string('code', 50); // e.g. intent, height, body_type, smoking, pets
            $table->string('field_type', 30)->default('text'); // text, number, select, multiselect, boolean, radio
            $table->boolean('is_required')->default(false);
            $table->boolean('is_searchable')->default(true);
            $table->boolean('is_filterable')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->json('validation_rules')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'code']);
            $table->index('is_filterable');
        });

        Schema::create('category_attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_attribute_id')->constrained('category_attributes')->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('value', 100);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['category_attribute_id', 'display_order'], 'cat_attr_opts_attr_disp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_attribute_options');
        Schema::dropIfExists('category_attributes');
        Schema::dropIfExists('categories');
    }
};
