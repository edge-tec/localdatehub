<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 3: Age Assurance, Consent & User Safety
     */
    public function up(): void
    {
        Schema::create('age_assurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('method', 50); // self_declaration, government_id, credit_card_verification, third_party_token
            $table->string('status', 30)->default('pending'); // pending, approved, rejected, expired
            $table->date('declared_birthdate')->nullable();
            $table->string('document_hash')->nullable();
            $table->string('third_party_reference')->nullable();
            $table->json('verification_metadata')->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('consent_type', 60); // adult_terms_18_plus, privacy_policy, anti_trafficking_acknowledgement, geolocation_consent
            $table->string('version', 20)->default('1.0');
            $table->boolean('is_accepted')->default(true);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('consented_at');

            $table->index(['user_id', 'consent_type']);
        });

        Schema::create('user_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 150)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['blocker_id', 'blocked_id']);
            $table->index('blocked_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('user_consents');
        Schema::dropIfExists('age_assurances');
    }
};
