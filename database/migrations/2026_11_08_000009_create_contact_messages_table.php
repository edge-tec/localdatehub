<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema 9: Contact Messages & Inquiries
     */
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('sender_name', 100);
            $table->text('sender_email_encrypted'); // Encrypted sensitive contact data
            $table->text('sender_phone_encrypted')->nullable(); // Encrypted sensitive contact data
            
            $table->string('subject', 150)->nullable();
            $table->text('message_encrypted'); // Encrypted message payload
            
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_replied')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->string('flagged_reason', 150)->nullable();
            
            $table->string('client_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['recipient_id', 'is_read']);
            $table->index(['listing_id', 'created_at']);
            $table->index('sender_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
