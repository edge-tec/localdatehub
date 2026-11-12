<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listing_contacts')) {
            Schema::table('listing_contacts', function (Blueprint $table) {
                if (!Schema::hasColumn('listing_contacts', 'relay_token_hash')) {
                    $table->string('relay_token_hash', 64)->nullable()->unique()->after('masked_relay_email');
                }
                if (!Schema::hasColumn('listing_contacts', 'relay_status')) {
                    $table->string('relay_status', 24)->default('active')->index()->after('relay_token_hash');
                }
            });

            // Backfill relay_token_hash for existing masked_relay_email records safely
            DB::table('listing_contacts')
                ->whereNotNull('masked_relay_email')
                ->whereNull('relay_token_hash')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $normalized = strtolower(trim((string) $row->masked_relay_email));
                        if ($normalized !== '') {
                            DB::table('listing_contacts')
                                ->where('id', $row->id)
                                ->update([
                                    'relay_token_hash' => hash('sha256', $normalized),
                                    'relay_status' => $row->allow_email_relay ? 'active' : 'disabled',
                                ]);
                        }
                    }
                });
        }

        if (!Schema::hasTable('email_relay_conversations')) {
            Schema::create('email_relay_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
                $table->foreignId('relay_contact_id')->constrained('listing_contacts')->cascadeOnDelete();
                $table->string('conversation_token', 40)->unique();
                $table->string('reply_token_hash', 64)->unique();
                $table->text('visitor_email_encrypted');
                $table->string('visitor_name', 120)->nullable();
                $table->text('advertiser_email_encrypted');
                $table->string('subject', 255);
                $table->string('last_message_id', 255)->nullable();
                $table->string('status', 24)->default('active')->index();
                $table->timestamp('last_activity_at')->nullable()->index();
                $table->timestamps();

                $table->index(['listing_id', 'last_activity_at'], 'erc_listing_activity_idx');
            });
        }

        if (!Schema::hasTable('email_relay_messages')) {
            Schema::create('email_relay_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
                $table->foreignId('relay_contact_id')->constrained('listing_contacts')->cascadeOnDelete();
                $table->foreignId('conversation_id')->constrained('email_relay_conversations')->cascadeOnDelete();
                $table->string('direction', 16)->index(); // inbound | outbound
                $table->string('sender_name', 120)->nullable();
                $table->text('sender_email_encrypted');
                $table->text('recipient_email_encrypted');
                $table->string('subject', 255);
                $table->longText('message_body_encrypted');
                $table->string('provider', 32)->default('smtp')->index();
                $table->string('provider_message_id', 255)->nullable()->index();
                $table->string('message_id', 255)->unique();
                $table->string('in_reply_to', 255)->nullable();
                $table->text('references_header')->nullable();
                $table->json('attachments_meta')->nullable();
                $table->string('delivery_status', 24)->default('queued')->index(); // received, queued, accepted, delivered, bounced, failed, blocked
                $table->unsignedSmallInteger('spam_score')->default(0);
                $table->text('error_message')->nullable();
                $table->string('sender_ip', 45)->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();

                $table->index(['listing_id', 'created_at'], 'erm_listing_created_idx');
                $table->index(['delivery_status', 'created_at'], 'erm_status_created_idx');
            });
        }

        if (!Schema::hasTable('email_relay_events')) {
            Schema::create('email_relay_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('relay_message_id')->constrained('email_relay_messages')->cascadeOnDelete();
                $table->string('event_type', 40)->index();
                $table->string('provider_event_id', 191)->nullable()->unique();
                $table->json('event_payload')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['relay_message_id', 'event_type'], 'ere_msg_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_relay_events');
        Schema::dropIfExists('email_relay_messages');
        Schema::dropIfExists('email_relay_conversations');

        if (Schema::hasTable('listing_contacts')) {
            Schema::table('listing_contacts', function (Blueprint $table) {
                if (Schema::hasColumn('listing_contacts', 'relay_token_hash')) {
                    $table->dropUnique(['relay_token_hash']);
                    $table->dropColumn('relay_token_hash');
                }
                if (Schema::hasColumn('listing_contacts', 'relay_status')) {
                    $table->dropColumn('relay_status');
                }
            });
        }
    }
};

