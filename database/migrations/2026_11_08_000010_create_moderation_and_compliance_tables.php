<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 10: Moderation Queues, Content Reports, Suspensions & Appeals
     */
    public function up(): void
    {
        Schema::create('moderation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('rule_type', 30); // keyword, regex, ip, fingerprint
            $table->text('pattern');
            $table->string('action_severity', 30)->default('flag'); // flag, auto_reject, shadowban, auto_suspend
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['rule_type', 'is_active']);
        });

        Schema::create('moderation_queue', function (Blueprint $table) {
            $table->id();
            $table->string('moderatable_type', 100); // Listing, Message, Media, User
            $table->unsignedBigInteger('moderatable_id');
            $table->string('status', 30)->default('queued'); // queued, in_review, approved, rejected, dismissed
            $table->string('trigger_reason', 150)->nullable();
            $table->json('automated_flags')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['moderatable_type', 'moderatable_id']);
            $table->index('status');
            $table->index('assigned_to_user_id');
        });

        Schema::create('moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moderation_queue_id')->nullable()->constrained('moderation_queue')->nullOnDelete();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action_taken', 50); // approved, rejected, listing_suspended, user_warned, user_banned
            $table->string('reason', 150);
            $table->text('internal_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['admin_user_id', 'created_at']);
        });

        Schema::create('content_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reportable_type', 100);
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason_code', 50); // commercial_solicitation, minor_exploitation, harassment, fraud_scam, non_consensual, other
            $table->text('details');
            $table->json('evidence_urls')->nullable();
            $table->string('status', 30)->default('open'); // open, investigating, resolved, dismissed
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('reporter_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index('status');
            $table->index('reason_code');
        });

        Schema::create('user_suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('issued_by_admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 150);
            $table->text('notes')->nullable();
            $table->string('suspension_type', 30)->default('temporary'); // temporary, permanent, shadowban
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_lifted')->default(false);
            $table->string('lifted_reason', 150)->nullable();
            $table->timestamp('lifted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_lifted', 'ends_at']);
        });

        Schema::create('appeal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_suspension_id')->nullable()->constrained('user_suspensions')->nullOnDelete();
            $table->text('appeal_reason');
            $table->string('status', 30)->default('pending'); // pending, under_review, granted, rejected
            $table->text('decision_notes')->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appeal_requests');
        Schema::dropIfExists('user_suspensions');
        Schema::dropIfExists('content_reports');
        Schema::dropIfExists('moderation_actions');
        Schema::dropIfExists('moderation_queue');
        Schema::dropIfExists('moderation_rules');
    }
};
