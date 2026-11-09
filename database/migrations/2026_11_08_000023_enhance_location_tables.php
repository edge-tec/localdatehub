<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 23: Location Engine Enhancements (State Slugs and City Place Types)
     */
    public function up(): void
    {
        Schema::table('states', function (Blueprint $table) {
            if (!Schema::hasColumn('states', 'slug')) {
                $table->string('slug', 80)->nullable()->after('name');
                $table->index('slug');
            }
        });

        Schema::table('cities', function (Blueprint $table) {
            if (!Schema::hasColumn('cities', 'city_type')) {
                $table->string('city_type', 30)->nullable()->after('slug');
                $table->index(['state_id', 'city_type']);
            }
        });

        // Add unique indexes for counties and zip_codes to support fast bulk upserts
        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS counties_state_id_name_unique ON counties(state_id, name)');
        } catch (\Throwable $e) {}

        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS zip_codes_unique_idx ON zip_codes(state_id, zip_code, city_id)');
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            if (Schema::hasColumn('cities', 'city_type')) {
                $table->dropIndex(['state_id', 'city_type']);
                $table->dropColumn('city_type');
            }
        });

        Schema::table('states', function (Blueprint $table) {
            if (Schema::hasColumn('states', 'slug')) {
                $table->dropIndex(['slug']);
                $table->dropColumn('slug');
            }
        });
    }
};
