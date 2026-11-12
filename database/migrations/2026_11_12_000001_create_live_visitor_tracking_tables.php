<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visitor_geolocation')) {
            Schema::create('visitor_geolocation', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->unique();
                $table->string('country_code', 8)->default('US')->index();
                $table->string('country_name', 100)->default('United States');
                $table->string('region_name', 120)->nullable();
                $table->string('city_name', 120)->nullable();
                $table->decimal('latitude', 10, 6)->nullable();
                $table->decimal('longitude', 10, 6)->nullable();
                $table->string('isp', 160)->nullable();
                $table->string('organization', 160)->nullable();
                $table->string('source', 30)->default('headers');
                $table->timestamp('cached_until')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('visitor_sessions')) {
            Schema::create('visitor_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('visitor_id', 64)->index();
                $table->string('session_token', 80)->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable()->index();
                $table->string('country_code', 8)->default('US')->index();
                $table->string('country_name', 100)->default('United States');
                $table->string('region_name', 120)->nullable();
                $table->string('city_name', 120)->nullable()->index();
                $table->decimal('latitude', 10, 6)->nullable();
                $table->decimal('longitude', 10, 6)->nullable();
                $table->string('isp', 160)->nullable();
                $table->string('organization', 160)->nullable();
                $table->string('device_type', 20)->default('Desktop')->index();
                $table->string('device_brand', 60)->nullable();
                $table->string('device_model', 80)->nullable();
                $table->string('os_name', 50)->default('Unknown');
                $table->string('os_version', 40)->nullable();
                $table->string('browser_name', 50)->default('Unknown');
                $table->string('browser_version', 40)->nullable();
                $table->string('screen_resolution', 32)->nullable();
                $table->string('landing_page', 500)->nullable();
                $table->string('current_page_url', 500)->nullable();
                $table->string('current_page_title', 255)->nullable();
                $table->string('referrer_url', 500)->nullable();
                $table->string('traffic_source', 50)->default('Direct')->index();
                $table->string('utm_source', 100)->nullable();
                $table->string('utm_medium', 100)->nullable();
                $table->string('utm_campaign', 120)->nullable();
                $table->string('utm_term', 120)->nullable();
                $table->string('utm_content', 120)->nullable();
                $table->unsignedInteger('pageviews_count')->default(1);
                $table->boolean('is_returning')->default(false)->index();
                $table->string('status', 20)->default('online')->index();
                $table->timestamp('first_seen_at')->useCurrent()->index();
                $table->timestamp('last_activity_at')->useCurrent()->index();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'last_activity_at']);
                $table->index(['country_code', 'city_name']);
            });
        }

        if (! Schema::hasTable('visitor_pageviews')) {
            Schema::create('visitor_pageviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visitor_session_id')->constrained('visitor_sessions')->cascadeOnDelete();
                $table->string('url', 500);
                $table->string('path', 255)->index();
                $table->string('page_title', 255)->nullable();
                $table->string('referrer_url', 500)->nullable();
                $table->unsignedInteger('response_time_ms')->nullable();
                $table->timestamp('viewed_at')->useCurrent()->index();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['visitor_session_id', 'viewed_at']);
            });
        }

        if (! Schema::hasTable('visitor_activity')) {
            Schema::create('visitor_activity', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visitor_session_id')->constrained('visitor_sessions')->cascadeOnDelete();
                $table->string('event_type', 30)->index();
                $table->string('page_url', 500)->nullable();
                $table->string('page_title', 255)->nullable();
                $table->string('details', 255)->nullable();
                $table->timestamp('occurred_at')->useCurrent()->index();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['visitor_session_id', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_activity');
        Schema::dropIfExists('visitor_pageviews');
        Schema::dropIfExists('visitor_sessions');
        Schema::dropIfExists('visitor_geolocation');
    }
};

