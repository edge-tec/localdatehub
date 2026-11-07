<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 4: US Geographic Location Engine
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique(); // US
            $table->string('name', 80); // United States
            $table->string('iso3', 3)->nullable(); // USA
            $table->string('phone_code', 10)->default('+1');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 2)->unique(); // CA, TX, NY, DC, etc.
            $table->string('fips_code', 5)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });

        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('fips_code', 10)->nullable();
            $table->timestamps();

            $table->index(['state_id', 'name']);
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('county_id')->nullable()->constrained('counties')->nullOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('population')->nullable();
            $table->string('timezone', 50)->default('America/New_York');
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['state_id', 'slug']);
            $table->index(['state_id', 'name']);
            $table->index(['latitude', 'longitude']);
            $table->index('is_popular');
            $table->index('is_active');
        });

        Schema::create('zip_codes', function (Blueprint $table) {
            $table->id();
            $table->string('zip_code', 10);
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index('zip_code');
            $table->index(['state_id', 'zip_code']);
            $table->index(['city_id', 'zip_code']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('location_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->string('alias', 100); // e.g. "Silicon Valley", "DFW Metro", "Manhattan"
            $table->string('alias_type', 30)->default('neighborhood'); // neighborhood, metro_area, colloquial
            $table->timestamps();

            $table->index(['alias', 'alias_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_aliases');
        Schema::dropIfExists('zip_codes');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('counties');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};
