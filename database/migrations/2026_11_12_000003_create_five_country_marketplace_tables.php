<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance countries table for Five-Country Marketplace Engine
        Schema::table('countries', function (Blueprint $table) {
            if (!Schema::hasColumn('countries', 'slug')) {
                $table->string('slug', 20)->nullable()->after('code')->index();
            }
            if (!Schema::hasColumn('countries', 'currency_code')) {
                $table->string('currency_code', 8)->default('USD')->after('phone_code');
            }
            if (!Schema::hasColumn('countries', 'currency_symbol')) {
                $table->string('currency_symbol', 8)->default('$')->after('currency_code');
            }
            if (!Schema::hasColumn('countries', 'flag')) {
                $table->string('flag', 24)->nullable()->after('currency_symbol');
            }
            if (!Schema::hasColumn('countries', 'timezone')) {
                $table->string('timezone', 60)->default('UTC')->after('flag');
            }
            if (!Schema::hasColumn('countries', 'division_label')) {
                $table->string('division_label', 40)->default('State / Region')->after('timezone');
            }
            if (!Schema::hasColumn('countries', 'seo_title')) {
                $table->string('seo_title', 180)->nullable()->after('division_label');
            }
            if (!Schema::hasColumn('countries', 'seo_description')) {
                $table->string('seo_description', 320)->nullable()->after('seo_title');
            }
            if (!Schema::hasColumn('countries', 'is_marketplace_enabled')) {
                $table->boolean('is_marketplace_enabled')->default(false)->after('is_active')->index();
            }
            if (!Schema::hasColumn('countries', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_marketplace_enabled');
            }
        });

        // 2. Enhance states table (Administrative Areas: States, Provinces, Regions, Counties, Nations)
        // Drop single-column unique index on states.code so codes like WA, NT, TAS can exist in different countries.
        try {
            Schema::table('states', function (Blueprint $table) {
                $table->dropUnique('states_code_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if already dropped or SQLite rebuilds below
        }

        Schema::table('states', function (Blueprint $table) {
            $table->string('code', 16)->change();

            if (!Schema::hasColumn('states', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('country_id')->constrained('states')->nullOnDelete();
            }
            if (!Schema::hasColumn('states', 'administrative_level')) {
                $table->string('administrative_level', 30)->default('state')->after('code')->index();
            }
            if (!Schema::hasColumn('states', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('is_territory');
            }
            if (!Schema::hasColumn('states', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        try {
            Schema::table('states', function (Blueprint $table) {
                $table->unique(['country_id', 'code'], 'states_country_id_code_unique');
            });
        } catch (\Throwable $e) {
        }

        // 3. Enhance cities table with country_id for direct country-level indexing
        Schema::table('cities', function (Blueprint $table) {
            if (!Schema::hasColumn('cities', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('id')->constrained('countries')->cascadeOnDelete();
                $table->index(['country_id', 'is_active', 'is_popular'], 'cities_country_active_popular_idx');
                $table->index(['country_id', 'slug'], 'cities_country_slug_idx');
            }
        });

        // Backfill cities.country_id from states.country_id
        try {
            DB::statement('UPDATE cities SET country_id = (SELECT country_id FROM states WHERE states.id = cities.state_id) WHERE country_id IS NULL');
        } catch (\Throwable $e) {
        }

        // 4. Enhance listings table with country_id, price, and currency_code
        Schema::table('listings', function (Blueprint $table) {
            if (!Schema::hasColumn('listings', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('category_id')->constrained('countries')->nullOnDelete();
                $table->index(['country_id', 'status', 'published_at'], 'listings_country_status_pub_idx');
                $table->index(['country_id', 'state_id', 'city_id', 'status'], 'listings_country_loc_status_idx');
                $table->index(['country_id', 'is_featured', 'status'], 'listings_country_featured_idx');
            }
            if (!Schema::hasColumn('listings', 'price')) {
                $table->decimal('price', 12, 2)->nullable()->after('intent');
            }
            if (!Schema::hasColumn('listings', 'currency_code')) {
                $table->string('currency_code', 8)->default('USD')->after('price');
            }
        });

        // Backfill listings.country_id from states.country_id
        try {
            DB::statement('UPDATE listings SET country_id = (SELECT country_id FROM states WHERE states.id = listings.state_id) WHERE country_id IS NULL');
        } catch (\Throwable $e) {
        }

        // 5. Country-specific Category Activation & Ordering Table
        if (!Schema::hasTable('country_categories')) {
            Schema::create('country_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['country_id', 'category_id'], 'country_categories_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('country_categories');

        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
            if (Schema::hasColumn('listings', 'price')) {
                $table->dropColumn('price');
            }
            if (Schema::hasColumn('listings', 'country_id')) {
                $table->dropIndex('listings_country_status_pub_idx');
                $table->dropIndex('listings_country_loc_status_idx');
                $table->dropIndex('listings_country_featured_idx');
                $table->dropConstrainedForeignId('country_id');
            }
        });

        Schema::table('cities', function (Blueprint $table) {
            if (Schema::hasColumn('cities', 'country_id')) {
                $table->dropIndex('cities_country_active_popular_idx');
                $table->dropIndex('cities_country_slug_idx');
                $table->dropConstrainedForeignId('country_id');
            }
        });
    }
};
