<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 33: Enterprise SEO, Indexing Queue, Keywords, Technical Audits & GSC Analytics
     */
    public function up(): void
    {
        // 1. URL Inventory & Health Records
        if (! Schema::hasTable('seo_url_records')) {
            Schema::create('seo_url_records', function (Blueprint $table) {
                $table->id();
                $table->string('url', 255)->unique();
                $table->string('url_type', 40)->default('listing'); // home, listing, category, location_state, location_city, cms_page, static
                $table->string('model_type', 100)->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->string('canonical_url', 255)->nullable();
                $table->string('indexing_status', 30)->default('discovered'); // discovered, queued, submitted, submission_failed, indexed, not_indexed, excluded, unknown
                $table->string('indexing_eligibility', 30)->default('eligible'); // eligible, ineligible, noindex, canonicalized, deleted
                $table->string('sitemap_status', 30)->default('included'); // included, excluded
                $table->string('sitemap_name', 60)->nullable();
                $table->unsignedSmallInteger('http_status')->default(200);
                $table->string('meta_title', 255)->nullable();
                $table->text('meta_description')->nullable();
                $table->string('h1', 255)->nullable();
                $table->timestamp('last_crawled_at')->nullable();
                $table->timestamp('last_indexed_check_at')->nullable();
                $table->timestamp('last_submitted_at')->nullable();
                $table->unsignedInteger('organic_clicks')->default(0);
                $table->unsignedInteger('organic_impressions')->default(0);
                $table->decimal('average_position', 6, 2)->default(0.00);
                $table->decimal('average_ctr', 5, 2)->default(0.00);
                $table->unsignedTinyInteger('seo_health_score')->default(100);
                $table->boolean('has_issues')->default(false);
                $table->timestamps();

                $table->index(['indexing_status', 'indexing_eligibility']);
                $table->index('url_type');
                $table->index(['model_type', 'model_id']);
                $table->index('has_issues');
                $table->index('sitemap_status');
            });
        }

        // 2. Keyword Groups & Topics
        if (! Schema::hasTable('seo_keyword_groups')) {
            Schema::create('seo_keyword_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 3. Keyword Database & Intent Classification
        if (! Schema::hasTable('seo_keywords')) {
            Schema::create('seo_keywords', function (Blueprint $table) {
                $table->id();
                $table->string('keyword', 191)->index();
                $table->foreignId('group_id')->nullable()->constrained('seo_keyword_groups')->nullOnDelete();
                $table->unsignedInteger('search_volume')->default(0);
                $table->unsignedTinyInteger('keyword_difficulty')->default(0); // 0-100
                $table->string('search_intent', 30)->default('commercial'); // informational, commercial, transactional, navigational
                $table->decimal('cpc', 8, 2)->default(0.00);
                $table->string('target_url', 255)->nullable();
                $table->json('related_keywords')->nullable();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
                $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
                $table->string('priority', 20)->default('medium'); // low, medium, high, critical
                $table->string('tracking_status', 30)->default('active'); // active, paused, archived
                $table->unsignedSmallInteger('ranking_position')->nullable();
                $table->string('data_source', 60)->default('internal_search'); // internal_search, google_search_console, dataforseo, semrush, manual
                $table->timestamp('last_retrieved_at')->nullable();
                $table->timestamps();

                $table->index(['category_id', 'tracking_status']);
                $table->index(['state_id', 'city_id']);
                $table->index('priority');
                $table->index('search_intent');
            });
        }

        // 4. Indexing Automation Queue
        if (! Schema::hasTable('seo_indexing_queue')) {
            Schema::create('seo_indexing_queue', function (Blueprint $table) {
                $table->id();
                $table->string('url', 255)->index();
                $table->foreignId('url_record_id')->nullable()->constrained('seo_url_records')->nullOnDelete();
                $table->string('status', 30)->default('pending'); // pending, processing, submitted, failed, cancelled
                $table->string('action', 30)->default('URL_UPDATED'); // URL_UPDATED, URL_DELETED
                $table->string('api_type', 40)->default('gsc_inspection'); // gsc_inspection, google_indexing_api, sitemap_ping
                $table->unsignedTinyInteger('priority')->default(5);
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedTinyInteger('max_attempts')->default(5);
                $table->text('last_error')->nullable();
                $table->timestamp('next_attempt_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->json('response_payload')->nullable();
                $table->timestamps();

                $table->index(['status', 'next_attempt_at']);
                $table->index('priority');
            });
        }

        // 5. Indexing Response & Audit Logs
        if (! Schema::hasTable('seo_indexing_logs')) {
            Schema::create('seo_indexing_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('queue_id')->nullable()->constrained('seo_indexing_queue')->nullOnDelete();
                $table->string('url', 255);
                $table->string('action', 30);
                $table->string('api_type', 40);
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->string('status', 30); // success, failed, rejected
                $table->longText('response_body')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['url', 'created_at']);
                $table->index('status');
            });
        }

        // 6. URL Redirects & 410 Handling
        if (! Schema::hasTable('seo_redirects')) {
            Schema::create('seo_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('source_path', 255)->unique();
                $table->string('target_url', 255)->nullable();
                $table->unsignedSmallInteger('status_code')->default(301); // 301, 302, 410
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('hits')->default(0);
                $table->timestamp('last_hit_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'source_path']);
            });
        }

        // 7. Technical SEO Issues & Audit Tracker
        if (! Schema::hasTable('seo_issues')) {
            Schema::create('seo_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('url_record_id')->nullable()->constrained('seo_url_records')->nullOnDelete();
                $table->string('url', 255)->index();
                $table->string('issue_type', 60); // missing_title, duplicate_title, missing_description, duplicate_description, missing_h1, multiple_h1, missing_alt, broken_internal_link, canonical_mismatch, thin_content, 404_error, 500_error
                $table->string('severity', 20)->default('warning'); // notice, warning, critical
                $table->text('description');
                $table->text('recommendation')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['issue_type', 'is_resolved']);
                $table->index(['severity', 'is_resolved']);
            });
        }

        // 8. Google Search Console & Search Performance Telemetry
        if (! Schema::hasTable('seo_search_analytics')) {
            Schema::create('seo_search_analytics', function (Blueprint $table) {
                $table->id();
                $table->date('date')->index();
                $table->string('query', 255)->nullable()->index();
                $table->string('page_url', 255)->nullable()->index();
                $table->string('country_code', 10)->nullable();
                $table->string('device', 20)->nullable();
                $table->unsignedInteger('clicks')->default(0);
                $table->unsignedInteger('impressions')->default(0);
                $table->decimal('ctr', 6, 4)->default(0.0000);
                $table->decimal('position', 6, 2)->default(0.00);
                $table->timestamps();

                $table->index(['date', 'clicks']);
                $table->index(['country_code', 'device']);
            });
        }

        // 9. SEO Content Suggestions & Planning
        if (! Schema::hasTable('seo_content_suggestions')) {
            Schema::create('seo_content_suggestions', function (Blueprint $table) {
                $table->id();
                $table->string('topic', 150);
                $table->string('target_type', 40)->default('general'); // category, location, general
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('suggested_title', 255);
                $table->json('suggested_keywords')->nullable();
                $table->string('search_intent', 30)->default('informational');
                $table->json('faq_suggestions')->nullable();
                $table->json('internal_link_targets')->nullable();
                $table->string('status', 30)->default('planned'); // planned, in_progress, implemented, ignored
                $table->timestamps();

                $table->index(['target_type', 'status']);
            });
        }

        // 10. Automated XML Sitemaps Registry
        if (! Schema::hasTable('seo_sitemaps')) {
            Schema::create('seo_sitemaps', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('file_path', 255)->nullable();
                $table->unsignedInteger('url_count')->default(0);
                $table->unsignedBigInteger('file_size_bytes')->default(0);
                $table->boolean('is_valid_xml')->default(true);
                $table->text('validation_error')->nullable();
                $table->timestamp('last_generated_at')->nullable();
                $table->timestamp('last_submitted_at')->nullable();
                $table->string('submission_status', 30)->default('not_submitted'); // not_submitted, submitted, failed
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_sitemaps');
        Schema::dropIfExists('seo_content_suggestions');
        Schema::dropIfExists('seo_search_analytics');
        Schema::dropIfExists('seo_issues');
        Schema::dropIfExists('seo_redirects');
        Schema::dropIfExists('seo_indexing_logs');
        Schema::dropIfExists('seo_indexing_queue');
        Schema::dropIfExists('seo_keywords');
        Schema::dropIfExists('seo_keyword_groups');
        Schema::dropIfExists('seo_url_records');
    }
};

