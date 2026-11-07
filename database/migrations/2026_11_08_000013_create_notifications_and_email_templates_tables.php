<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 13: Notifications and Email Templates
     */
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 100);
            $table->string('subject', 180);
            $table->longText('body_html');
            $table->text('body_text')->nullable();
            $table->json('available_variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel', 30); // email, sms, database, push
            $table->string('notification_type', 100);
            $table->string('subject', 180)->nullable();
            $table->string('recipient_target', 120); // email address or phone number
            $table->string('status', 30)->default('queued'); // queued, sent, failed
            $table->text('error_details')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('notification_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
        Schema::dropIfExists('email_templates');
    }
};
