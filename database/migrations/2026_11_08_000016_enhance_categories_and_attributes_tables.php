<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 16: Category Hierarchy Depth, Guidelines, and Attribute Enhancements
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('depth')->default(0)->after('parent_id');
            $table->text('safety_guidelines')->nullable()->after('description');
            
            $table->index('depth');
        });

        Schema::table('category_attributes', function (Blueprint $table) {
            $table->string('placeholder', 150)->nullable()->after('name');
            $table->string('help_text', 255)->nullable()->after('placeholder');
            $table->string('unit', 30)->nullable()->after('help_text'); // e.g. "inches", "lbs", "years"
            $table->string('default_value', 100)->nullable()->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_attributes', function (Blueprint $table) {
            $table->dropColumn(['placeholder', 'help_text', 'unit', 'default_value']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['depth']);
            $table->dropColumn(['depth', 'safety_guidelines']);
        });
    }
};
