<?php

namespace Tests\Feature;

use App\Jobs\EmailRelay\ForwardRelayEmail;
use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\EmailRelay\Models\EmailRelayConversation;
use App\Modules\EmailRelay\Models\EmailRelayEvent;
use App\Modules\EmailRelay\Models\EmailRelayMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingContact;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class EncryptedEmailRelayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'email_relay.enabled' => true,
            'email_relay.domain' => 'localdatehub.com',
            'email_relay.webhooks.cloudflare.secret' => 'cf-test-secret',
            'email_relay.webhooks.mailgun.signing_key' => 'mg-test-signing-key',
            'queue.default' => 'sync',
        ]);
    }

    protected function createActiveListingWithRelay(User $advertiser, string $title = 'Private Dinner Companion'): Listing
    {
        if (State::count() === 0) {
            $this->artisan('locations:import');
        }

        $state = State::first();
        $city = City::where('state_id', $state->id)->first();

        $category = Category::firstOrCreate(
            ['slug' => 'dating'],
            ['name' => 'Dating', 'display_order' => 1, 'is_active' => true]
        );

        $listing = Listing::create([
            'user_id' => $advertiser->id,
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city?->id,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'description' => 'Safe verified adult classified listing.',
            'poster_age' => 25,
            'status' => Listing::STATUS_ACTIVE,
        ]);

        ListingContact::create([
            'listing_id' => $listing->id,
            'allow_platform_messages' => true,
            'allow_email_relay' => true,
            'show_email' => false,
            'masked_relay_email' => 'relay-' . strtolower(Str::random(10)) . '@localdatehub.com',
            'preferred_email_encrypted' => $advertiser->email,
            'relay_status' => 'active',
        ]);

        return $listing->fresh('contact');
    }

    public function test_case_1_and_2_inbound_email_forwards_to_advertiser_and_reply_routes_back_privately(): void
    {
        Mail::fake();
        $advertiser = User::factory()->create(['email' => 'secret-advertiser@example.com']);
        $listing = $this->createActiveListingWithRelay($advertiser);

        // Case 1: Visitor emails masked relay address via Cloudflare Email Worker webhook
        $inboundRes = $this->withHeaders([
            'X-Relay-Secret' => 'cf-test-secret',
        ])->postJson('/webhooks/email-relay/inbound/cloudflare', [
            'to' => $listing->contact->masked_relay_email,
            'from' => 'visitor@gmail.com',
            'from_name' => 'Alex Visitor',
            'subject' => 'Hello from visitor',
            'text' => 'Hi there, I saw your listing and would love to connect.',
            'message_id' => '<visitor-msg-1@gmail.com>',
            'provider_event_id' => 'evt-in-1',
        ]);

        $inboundRes->assertStatus(202)->assertJson(['ok' => true]);

        $conversation = EmailRelayConversation::where('listing_id', $listing->id)->first();
        $this->assertNotNull($conversation);
        $this->assertSame('visitor@gmail.com', $conversation->visitor_email_encrypted);
        $this->assertSame('secret-advertiser@example.com', $conversation->advertiser_email_encrypted);

        $inboundMsg = EmailRelayMessage::where('conversation_id', $conversation->id)->first();
        $this->assertNotNull($inboundMsg);
        // SMTP accepted sets 'accepted', NOT 'delivered'
        $this->assertSame(EmailRelayMessage::STATUS_ACCEPTED, $inboundMsg->delivery_status);
        $this->assertNotNull($inboundMsg->sent_at);
        $this->assertNull($inboundMsg->delivered_at);

        // Case 2: Advertiser clicks Reply -> sends to reply-<token>@localdatehub.com
        $replyAddress = 'reply-' . $conversation->conversation_token . '@localdatehub.com';
        $replyRes = $this->withHeaders([
            'X-Relay-Secret' => 'cf-test-secret',
        ])->postJson('/webhooks/email-relay/inbound/cloudflare', [
            'to' => $replyAddress,
            'from' => 'secret-advertiser@example.com',
            'from_name' => 'Advertiser',
            'subject' => 'Re: Hello from visitor',
            'text' => 'Thanks for reaching out! Here is my private reply.',
            'message_id' => '<adv-reply-1@example.com>',
            'in_reply_to' => $conversation->last_message_id,
            'provider_event_id' => 'evt-reply-1',
        ]);

        $replyRes->assertStatus(202)->assertJson(['ok' => true]);

        $replyMsg = EmailRelayMessage::where('message_id', '<adv-reply-1@example.com>')->first();
        $this->assertNotNull($replyMsg);
        $this->assertSame('visitor@gmail.com', $replyMsg->recipient_email_encrypted);
        $this->assertSame(EmailRelayMessage::STATUS_ACCEPTED, $replyMsg->delivery_status);
        $this->assertNotNull($replyMsg->in_reply_to);
    }

    public function test_case_3_in_app_email_composer_sends_relay_and_hides_advertiser_email(): void
    {
        Mail::fake();
        $advertiser = User::factory()->create(['email' => 'hidden-owner@example.com']);
        $listing = $this->createActiveListingWithRelay($advertiser);

        // Listing page renders compose modal and never leaks advertiser's real email
        $showRes = $this->get(route('listings.show', $listing->slug));
        $showRes->assertStatus(200)
            ->assertSee('Send Encrypted Email')
            ->assertSee($listing->contact->masked_relay_email)
            ->assertDontSee('hidden-owner@example.com')
            ->assertDontSee('mailto:' . $listing->contact->masked_relay_email);

        $res = $this->postJson(route('listings.email-relay.send', $listing->id), [
            'sender_name' => 'Jordan',
            'sender_email' => 'jordan@example.org',
            'subject' => 'Interested in meeting',
            'message' => 'Hello, I would like to chat about your listing this weekend.',
        ]);

        $res->assertStatus(202)
            ->assertJson([
                'ok' => true,
                'message' => 'Your message has been securely sent through LocalDateHub. Your personal email address remains private.',
            ]);

        $this->assertStringNotContainsString('hidden-owner@example.com', $res->getContent());
        $this->assertDatabaseHas('email_relay_messages', [
            'listing_id' => $listing->id,
            'direction' => EmailRelayMessage::DIRECTION_OUTBOUND,
            'delivery_status' => EmailRelayMessage::STATUS_ACCEPTED,
        ]);
    }

    public function test_case_4_invalid_or_disabled_relay_address_is_rejected_safely(): void
    {
        Mail::fake();
        $advertiser = User::factory()->create();
        $listing = $this->createActiveListingWithRelay($advertiser);
        $listing->contact->update(['relay_status' => 'disabled', 'allow_email_relay' => false]);

        $this->withHeaders(['X-Relay-Secret' => 'cf-test-secret'])
            ->postJson('/webhooks/email-relay/inbound/cloudflare', [
                'to' => $listing->contact->masked_relay_email,
                'from' => 'visitor@gmail.com',
                'subject' => 'Test',
                'text' => 'Should be rejected.',
                'message_id' => '<disabled-1@gmail.com>',
            ])->assertStatus(404);

        $this->withHeaders(['X-Relay-Secret' => 'cf-test-secret'])
            ->postJson('/webhooks/email-relay/inbound/cloudflare', [
                'to' => 'relay-nonexistent99@localdatehub.com',
                'from' => 'visitor@gmail.com',
                'subject' => 'Test',
                'text' => 'Unknown relay.',
                'message_id' => '<unknown-1@gmail.com>',
            ])->assertStatus(404);

        $this->postJson(route('listings.email-relay.send', $listing->id), [
            'sender_name' => 'Jordan',
            'sender_email' => 'jordan@example.org',
            'subject' => 'Interested',
            'message' => 'Trying to contact a disabled relay listing.',
        ])->assertStatus(422);
    }

    public function test_case_5_duplicate_webhook_event_is_idempotent(): void
    {
        Mail::fake();
        $advertiser = User::factory()->create();
        $listing = $this->createActiveListingWithRelay($advertiser);

        $payload = [
            'to' => $listing->contact->masked_relay_email,
            'from' => 'visitor@gmail.com',
            'subject' => 'Idempotency check',
            'text' => 'Sending this webhook twice must create only one message.',
            'message_id' => '<dup-msg-1@gmail.com>',
            'provider_event_id' => 'evt-dup-123',
        ];

        $this->withHeaders(['X-Relay-Secret' => 'cf-test-secret'])
            ->postJson('/webhooks/email-relay/inbound/cloudflare', $payload)
            ->assertStatus(202);

        $this->withHeaders(['X-Relay-Secret' => 'cf-test-secret'])
            ->postJson('/webhooks/email-relay/inbound/cloudflare', $payload)
            ->assertStatus(202);

        $this->assertSame(1, EmailRelayMessage::where('message_id', '<dup-msg-1@gmail.com>')->count());
    }

    public function test_case_6_smtp_failure_records_failed_status_and_error_message(): void
    {
        $advertiser = User::factory()->create();
        $listing = $this->createActiveListingWithRelay($advertiser);

        $conv = EmailRelayConversation::create([
            'listing_id' => $listing->id,
            'relay_contact_id' => $listing->contact->id,
            'conversation_token' => 'convfailtoken123456',
            'reply_token_hash' => hash('sha256', 'convfailtoken123456'),
            'visitor_email_encrypted' => 'visitor@gmail.com',
            'advertiser_email_encrypted' => $advertiser->email,
            'subject' => 'SMTP failure test',
            'status' => 'active',
        ]);

        $msg = EmailRelayMessage::create([
            'listing_id' => $listing->id,
            'relay_contact_id' => $listing->contact->id,
            'conversation_id' => $conv->id,
            'direction' => EmailRelayMessage::DIRECTION_OUTBOUND,
            'sender_name' => 'Visitor',
            'sender_email_encrypted' => 'visitor@gmail.com',
            'recipient_email_encrypted' => $advertiser->email,
            'subject' => 'SMTP failure test',
            'message_body_encrypted' => 'Body content',
            'provider' => 'smtp',
            'message_id' => '<fail-test-1@localdatehub.com>',
            'delivery_status' => EmailRelayMessage::STATUS_QUEUED,
        ]);

        (new ForwardRelayEmail($msg->id))->failed(new RuntimeException('SMTP connection timed out'));

        $msg->refresh();
        $this->assertSame(EmailRelayMessage::STATUS_FAILED, $msg->delivery_status);
        $this->assertNotNull($msg->failed_at);
        $this->assertStringContainsString('SMTP connection timed out', (string) $msg->error_message);
        $this->assertDatabaseHas('email_relay_events', [
            'relay_message_id' => $msg->id,
            'event_type' => EmailRelayMessage::STATUS_FAILED,
        ]);
    }

    public function test_case_7_webhook_signature_validation_rejects_invalid_requests(): void
    {
        $this->withHeaders(['X-Relay-Secret' => 'wrong-secret'])
            ->postJson('/webhooks/email-relay/inbound/cloudflare', ['to' => 'relay-abc@localdatehub.com'])
            ->assertStatus(403);

        $this->postJson('/webhooks/email-relay/status/mailgun', [
            'timestamp' => (string) time(),
            'token' => 'abc',
            'signature' => 'invalid-hmac',
        ])->assertStatus(403);

        // Valid Mailgun HMAC-SHA256 status callback updates accepted -> delivered
        Mail::fake();
        $advertiser = User::factory()->create();
        $listing = $this->createActiveListingWithRelay($advertiser);
        $this->postJson(route('listings.email-relay.send', $listing->id), [
            'sender_name' => 'Jordan',
            'sender_email' => 'jordan@example.org',
            'subject' => 'Delivery Webhook Test',
            'message' => 'Testing provider webhook delivery confirmation.',
        ])->assertStatus(202);

        $msg = EmailRelayMessage::first();
        $ts = (string) time();
        $tok = 'tok12345';
        $sig = hash_hmac('sha256', $ts . $tok, 'mg-test-signing-key');

        $this->postJson('/webhooks/email-relay/status/mailgun', [
            'timestamp' => $ts,
            'token' => $tok,
            'signature' => $sig,
            'event' => 'delivered',
            'event_id' => 'mg-evt-1',
            'relay_message_id' => $msg->id,
        ])->assertOk()->assertJson(['ok' => true, 'delivery_status' => 'delivered']);

        $this->assertSame(EmailRelayMessage::STATUS_DELIVERED, $msg->fresh()->delivery_status);
        $this->assertNotNull($msg->fresh()->delivered_at);
    }

    public function test_case_8_multiple_listings_same_advertiser_route_to_exact_listing(): void
    {
        Mail::fake();
        $advertiser = User::factory()->create(['email' => 'multi-owner@example.com']);
        $listingA = $this->createActiveListingWithRelay($advertiser, 'Listing Alpha');
        $listingB = $this->createActiveListingWithRelay($advertiser, 'Listing Beta');

        $this->withHeaders(['X-Relay-Secret' => 'cf-test-secret'])
            ->postJson('/webhooks/email-relay/inbound/cloudflare', [
                'to' => $listingB->contact->masked_relay_email,
                'from' => 'fan@gmail.com',
                'subject' => 'For Beta Only',
                'text' => 'Messaging Listing Beta specifically.',
                'message_id' => '<beta-1@gmail.com>',
            ])->assertStatus(202);

        $msg = EmailRelayMessage::where('message_id', '<beta-1@gmail.com>')->first();
        $this->assertSame($listingB->id, $msg->listing_id);
        $this->assertNotSame($listingA->id, $msg->listing_id);
    }

    public function test_case_9_spam_and_rate_limit_protection(): void
    {
        Mail::fake();
        config(['email_relay.rate_limit.per_ip_per_minute' => 2]);
        $advertiser = User::factory()->create();
        $listing = $this->createActiveListingWithRelay($advertiser);

        // Spam payload blocked
        $this->postJson(route('listings.email-relay.send', $listing->id), [
            'sender_name' => 'Spammer',
            'sender_email' => 'spammer@example.com',
            'subject' => 'Crypto giveaway urgent',
            'message' => 'Claim bitcoin investment via wire transfer at https://a.test https://b.test https://c.test https://d.test',
        ])->assertStatus(422);

        $this->assertDatabaseHas('email_relay_messages', [
            'listing_id' => $listing->id,
            'delivery_status' => EmailRelayMessage::STATUS_BLOCKED,
        ]);

        // Rate limit enforced after per_ip_per_minute hits
        $this->postJson(route('listings.email-relay.send', $listing->id), [
            'sender_name' => 'Normal User',
            'sender_email' => 'normal@example.com',
            'subject' => 'Hello there',
            'message' => 'Valid inquiry number two within the same minute.',
        ])->assertStatus(202);

        $this->postJson(route('listings.email-relay.send', $listing->id), [
            'sender_name' => 'Normal User',
            'sender_email' => 'normal@example.com',
            'subject' => 'Hello again',
            'message' => 'Third inquiry within one minute should be rate limited.',
        ])->assertStatus(429);
    }

    public function test_case_10_attachment_validation_and_admin_dashboard_controls(): void
    {
        Mail::fake();
        Storage::fake('local');
        $advertiser = User::factory()->create();
        $listing = $this->createActiveListingWithRelay($advertiser);

        // Disallowed executable attachment rejected
        $badFile = UploadedFile::fake()->create('payload.exe', 100, 'application/x-msdownload');
        $this->postJson(route('listings.email-relay.send', $listing->id), [
            'sender_name' => 'Visitor',
            'sender_email' => 'visitor@example.com',
            'subject' => 'With bad attachment',
            'message' => 'Testing disallowed executable file upload.',
            'attachments' => [$badFile],
        ])->assertStatus(422);

        // Allowed JPG attachment accepted and stored safely
        $goodFile = UploadedFile::fake()->image('photo.jpg', 200, 200);
        $this->postJson(route('listings.email-relay.send', $listing->id), [
            'sender_name' => 'Visitor',
            'sender_email' => 'visitor@example.com',
            'subject' => 'With valid image',
            'message' => 'Testing allowed image attachment through relay.',
            'attachments' => [$goodFile],
        ])->assertStatus(202);

        $msg = EmailRelayMessage::latest('id')->first();
        $this->assertNotEmpty($msg->attachments_meta);
        Storage::disk('local')->assertExists($msg->attachments_meta[0]['storage_path']);

        // Admin Dashboard Email Relay Management tab renders and toggles controls
        $admin = User::factory()->create(['role' => 'super_admin']);
        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class)
            ->call('setTab', 'email_relay')
            ->assertSee('Encrypted Email Relay')
            ->assertSee($listing->contact->masked_relay_email)
            ->call('toggleListingRelayStatus', $listing->contact->id)
            ->call('toggleGlobalEmailRelay');

        $this->assertSame('disabled', $listing->contact->fresh()->relay_status);
    }
}
