<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('legacy_placement_id')->nullable()->constrained('ad_placements')->nullOnDelete();
            $table->string('name', 150);
            $table->string('campaign_type', 30)->default('banner'); // banner, affiliate, hybrid
            $table->string('advertiser_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->text('destination_url')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('status', 30)->default('draft'); // draft, pending_approval, scheduled, active, paused, expired, rejected, archived
            $table->integer('priority')->default(10);
            $table->unsignedInteger('weight')->default(100);
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->unsignedBigInteger('daily_budget_cents')->nullable();
            $table->unsignedBigInteger('total_budget_cents')->nullable();
            $table->unsignedBigInteger('spent_cents')->default(0);
            $table->unsignedInteger('cpc_cents')->nullable();
            $table->unsignedInteger('cpm_cents')->nullable();
            $table->unsignedBigInteger('max_impressions')->nullable();
            $table->unsignedBigInteger('max_clicks')->nullable();
            $table->unsignedBigInteger('daily_impression_cap')->nullable();
            $table->unsignedBigInteger('daily_click_cap')->nullable();
            $table->unsignedSmallInteger('frequency_cap_per_session')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('total_impressions')->default(0);
            $table->unsignedBigInteger('total_unique_impressions')->default(0);
            $table->unsignedBigInteger('total_clicks')->default(0);
            $table->unsignedBigInteger('total_unique_clicks')->default(0);
            $table->unsignedBigInteger('total_conversions')->default(0);
            $table->unsignedBigInteger('total_conversion_revenue_cents')->default(0);
            $table->string('postback_secret', 80)->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at', 'priority'], 'ad_campaigns_delivery_idx');
            $table->index('campaign_type');
        });

        Schema::create('ad_creatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->string('format', 30)->default('banner'); // banner, affiliate
            $table->string('title', 160)->nullable();
            $table->text('short_description')->nullable();
            $table->string('cta_text', 60)->nullable();
            $table->text('destination_url')->nullable();
            $table->string('desktop_banner_url', 500)->nullable();
            $table->string('mobile_banner_url', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->string('affiliate_brand_name', 150)->nullable();
            $table->text('affiliate_tracking_url')->nullable();
            $table->string('offer_badge', 60)->nullable();
            $table->string('disclosure_label', 40)->default('Sponsored'); // Sponsored, Advertisement, Promoted, Affiliate Offer
            $table->timestamp('offer_expires_at')->nullable();
            $table->string('subid_param', 60)->nullable();
            $table->string('click_id_param', 60)->nullable();
            $table->json('extra_tracking_params')->nullable();
            $table->text('html_snippet')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'is_active']);
        });

        Schema::create('ad_campaign_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->string('placement_slot', 60);
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['campaign_id', 'placement_slot'], 'ad_campaign_slot_unique');
            $table->index(['placement_slot', 'is_active']);
        });

        Schema::create('ad_targeting_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->string('rule_type', 40); // country, state, city, zip_code, category, subcategory, device
            $table->string('rule_value', 120);
            $table->string('match_mode', 20)->default('include'); // include, exclude
            $table->timestamps();

            $table->index(['campaign_id', 'rule_type']);
        });

        Schema::create('ad_placement_configs', function (Blueprint $table) {
            $table->id();
            $table->string('slot', 60)->unique();
            $table->string('label', 120);
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('insert_every_n')->default(6);
            $table->unsignedSmallInteger('max_ads_per_page')->default(3);
            $table->string('rotation_mode', 30)->default('priority_weighted'); // priority_weighted, random, round_robin
            $table->boolean('dedupe_session')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignId('creative_id')->nullable()->constrained('ad_creatives')->nullOnDelete();
            $table->string('placement_slot', 60);
            $table->string('visitor_hash', 64);
            $table->string('session_id', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('device_type', 20)->default('desktop');
            $table->string('country_code', 10)->nullable();
            $table->string('state_code', 10)->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('page_url', 500)->nullable();
            $table->boolean('is_unique')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['campaign_id', 'created_at']);
            $table->index(['visitor_hash', 'campaign_id', 'placement_slot', 'created_at'], 'ad_impr_dedupe_idx');
        });

        Schema::create('ad_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('click_token', 64)->unique();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignId('creative_id')->nullable()->constrained('ad_creatives')->nullOnDelete();
            $table->string('placement_slot', 60);
            $table->string('visitor_hash', 64);
            $table->string('session_id', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('device_type', 20)->default('desktop');
            $table->string('country_code', 10)->nullable();
            $table->string('state_code', 10)->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('destination_url');
            $table->boolean('is_unique')->default(true);
            $table->boolean('is_suspicious')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['campaign_id', 'created_at']);
            $table->index(['visitor_hash', 'campaign_id', 'created_at'], 'ad_clicks_visitor_idx');
        });

        Schema::create('ad_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('conversion_token', 64)->unique();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignId('click_id')->nullable()->constrained('ad_clicks')->nullOnDelete();
            $table->string('external_transaction_id', 120)->nullable();
            $table->string('sub_id', 120)->nullable();
            $table->unsignedBigInteger('payout_cents')->default(0);
            $table->unsignedBigInteger('revenue_cents')->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 30)->default('confirmed'); // confirmed, pending, rejected
            $table->string('verified_via', 40)->default('postback_token');
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['campaign_id', 'external_transaction_id'], 'ad_conversions_ext_tx_unique');
            $table->index(['campaign_id', 'created_at']);
        });

        Schema::create('ad_daily_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->string('placement_slot', 60);
            $table->string('device_type', 20)->default('desktop');
            $table->string('state_code', 10)->default('');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('unique_impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('unique_clicks')->default(0);
            $table->unsignedBigInteger('conversions')->default(0);
            $table->unsignedBigInteger('revenue_cents')->default(0);
            $table->unsignedBigInteger('spend_cents')->default(0);
            $table->timestamps();

            $table->unique(['date', 'campaign_id', 'placement_slot', 'device_type', 'state_code'], 'ad_daily_stats_composite_unique');
            $table->index(['date', 'placement_slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_daily_statistics');
        Schema::dropIfExists('ad_conversions');
        Schema::dropIfExists('ad_clicks');
        Schema::dropIfExists('ad_impressions');
        Schema::dropIfExists('ad_placement_configs');
        Schema::dropIfExists('ad_targeting_rules');
        Schema::dropIfExists('ad_campaign_placements');
        Schema::dropIfExists('ad_creatives');
        Schema::dropIfExists('ad_campaigns');
    }
};

