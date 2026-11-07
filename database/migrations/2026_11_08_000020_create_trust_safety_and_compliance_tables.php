<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 20: Trust, Safety, Compliance, DMCA, Legal Requests & Evidence Preservation
     */
    public function up(): void
    {
        // 1. Add risk level and urgency score to moderation queue if not exists
        Schema::table('moderation_queue', function (Blueprint $table) {
            if (!Schema::hasColumn('moderation_queue', 'risk_level')) {
                $table->string('risk_level', 20)->default('medium')->after('status'); // low, medium, high, critical
            }
            if (!Schema::hasColumn('moderation_queue', 'urgency_score')) {
                $table->unsignedSmallInteger('urgency_score')->default(50)->after('risk_level'); // 0-100
            }
        });

        // 2. Add risk level and takedown flag to content reports
        Schema::table('content_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('content_reports', 'risk_level')) {
                $table->string('risk_level', 20)->default('medium')->after('reason_code'); // low, medium, high, critical
            }
            if (!Schema::hasColumn('content_reports', 'is_urgent_takedown')) {
                $table->boolean('is_urgent_takedown')->default(false)->after('risk_level');
            }
        });

        // 3. DMCA Takedown & Counter-Notice Requests
        if (!Schema::hasTable('dmca_requests')) {
            Schema::create('dmca_requests', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_number', 50)->unique();
                $table->string('claimant_name', 120);
                $table->string('claimant_email', 120);
                $table->string('claimant_phone', 40)->nullable();
                $table->string('organization', 150)->nullable();
                $table->text('copyrighted_work');
                $table->string('infringing_url', 255);
                $table->foreignId('target_listing_id')->nullable()->constrained('listings')->nullOnDelete();
                $table->boolean('good_faith_attested')->default(true);
                $table->boolean('perjury_attested')->default(true);
                $table->string('digital_signature', 120);
                $table->string('status', 30)->default('pending'); // pending, takedown_issued, counter_noticed, rejected, resolved
                $table->text('counter_notice_details')->nullable();
                $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamps();

                $table->index('ticket_number');
                $table->index('status');
            });
        }

        // 4. Law Enforcement & Statutory Legal Requests
        if (!Schema::hasTable('legal_requests')) {
            Schema::create('legal_requests', function (Blueprint $table) {
                $table->id();
                $table->string('reference_code', 50)->unique();
                $table->string('agency_name', 150);
                $table->string('officer_name', 120);
                $table->string('badge_number', 60)->nullable();
                $table->string('official_email', 120);
                $table->string('court_jurisdiction', 150)->nullable();
                $table->string('request_type', 40); // subpoena, search_warrant, 2703f_preservation, 2257_audit, court_order, emergency_disclosure
                $table->string('case_number', 100);
                $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('target_listing_id')->nullable()->constrained('listings')->nullOnDelete();
                $table->text('description');
                $table->string('status', 30)->default('received'); // received, under_legal_review, fulfilled, rejected
                $table->string('risk_level', 20)->default('high'); // medium, high, critical
                $table->boolean('is_emergency')->default(false);
                $table->foreignId('handled_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('fulfilled_at')->nullable();
                $table->text('compliance_notes')->nullable();
                $table->timestamps();

                $table->index('reference_code');
                $table->index('status');
                $table->index('request_type');
            });
        }

        // 5. Evidence Preservation Vault (18 U.S.C. 2703(f) and Criminal Investigation Holds)
        if (!Schema::hasTable('evidence_preservations')) {
            Schema::create('evidence_preservations', function (Blueprint $table) {
                $table->id();
                $table->string('preservable_type', 100);
                $table->unsignedBigInteger('preservable_id');
                $table->foreignId('legal_request_id')->nullable()->constrained('legal_requests')->nullOnDelete();
                $table->string('hold_reason', 150);
                $table->json('snapshot_data');
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('frozen_by_admin_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['preservable_type', 'preservable_id']);
                $table->index('is_active');
            });
        }

        // 6. State-Specific Adult Compliance & Geofencing Configurations
        if (!Schema::hasTable('state_compliances')) {
            Schema::create('state_compliances', function (Blueprint $table) {
                $table->id();
                $table->string('state_code', 2)->unique();
                $table->string('state_name', 80);
                $table->boolean('requires_age_gate')->default(true);
                $table->boolean('requires_id_verification')->default(false);
                $table->unsignedSmallInteger('statutory_min_age')->default(18);
                $table->text('mandatory_warning_text')->nullable();
                $table->boolean('is_restricted')->default(false);
                $table->json('restricted_categories')->nullable();
                $table->string('legal_reference', 120)->nullable(); // e.g. TX HB 1181, LA Act 440, UT SB 287
                $table->timestamps();

                $table->index('state_code');
                $table->index('requires_id_verification');
            });
        }

        // 7. Policy Versions & Terms Audit Tracking
        if (!Schema::hasTable('policy_versions')) {
            Schema::create('policy_versions', function (Blueprint $table) {
                $table->id();
                $table->string('policy_type', 50); // terms_of_service, trust_and_safety, privacy_policy, dmca_policy, age_assurance_policy
                $table->string('version', 20); // 1.0, 1.1, 2.0
                $table->string('title', 150);
                $table->text('summary_of_changes')->nullable();
                $table->longText('content');
                $table->boolean('is_active')->default(false);
                $table->timestamp('effective_at');
                $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['policy_type', 'version']);
                $table->index(['policy_type', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_versions');
        Schema::dropIfExists('state_compliances');
        Schema::dropIfExists('evidence_preservations');
        Schema::dropIfExists('legal_requests');
        Schema::dropIfExists('dmca_requests');

        Schema::table('content_reports', function (Blueprint $table) {
            if (Schema::hasColumn('content_reports', 'is_urgent_takedown')) {
                $table->dropColumn('is_urgent_takedown');
            }
            if (Schema::hasColumn('content_reports', 'risk_level')) {
                $table->dropColumn('risk_level');
            }
        });

        Schema::table('moderation_queue', function (Blueprint $table) {
            if (Schema::hasColumn('moderation_queue', 'urgency_score')) {
                $table->dropColumn('urgency_score');
            }
            if (Schema::hasColumn('moderation_queue', 'risk_level')) {
                $table->dropColumn('risk_level');
            }
        });
    }
};
