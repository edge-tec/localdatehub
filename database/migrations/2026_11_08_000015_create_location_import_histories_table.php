<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 15: Location Import Version History & Territory Enhancements
     */
    public function up(): void
    {
        Schema::create('location_import_histories', function (Blueprint $table) {
            $table->id();
            $table->string('dataset_name', 50); // states, counties, cities, zips, aliases, full_suite
            $table->string('version', 30)->default('2026.1');
            $table->string('source_file', 255)->nullable();
            $table->string('checksum', 64)->nullable(); // SHA-256
            $table->unsignedInteger('records_processed')->default(0);
            $table->unsignedInteger('records_created')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->string('status', 20)->default('completed'); // started, completed, failed
            $table->unsignedInteger('duration_ms')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['dataset_name', 'status']);
            $table->index('created_at');
        });

        if (!Schema::hasColumn('states', 'is_territory')) {
            Schema::table('states', function (Blueprint $table) {
                $table->boolean('is_territory')->default(false)->after('fips_code');
                $table->index('is_territory');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_import_histories');

        if (Schema::hasColumn('states', 'is_territory')) {
            Schema::table('states', function (Blueprint $table) {
                $table->dropColumn('is_territory');
            });
        }
    }
};
