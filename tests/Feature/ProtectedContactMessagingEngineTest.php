<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingContact;
use App\Modules\Listings\Models\ListingLocation;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtectedContactMessagingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $advertiser;
    protected User $seeker;
    protected User $admin;
    protected Listing $activeListing;
    protected Category $category;
    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->category = Category::where('is_active', true)->first();
        $this->city = City::first();

        $this->advertiser = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $this->seeker = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->activeListing = Listing::create([
            'user_id' => $this->advertiser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Attractive Professional Seeking Romance in Downtown',
            'slug' => 'attractive-professional-romance-' . uniqid(),
            'description' => 'Hello! Outgoing, athletic professional looking to connect for exciting coffee dates and genuine connection. Must love outdoor adventures.',
            'content_hash' => hash('sha256', 'listing content' . uniqid()),
            'poster_age' => 29,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'favorite_count' => 0,
            'reply_count' => 0,
            'view_count' => 0,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        ListingLocation::create([
            'listing_id' => $this->activeListing->id,
            'latitude' => $this->city->latitude ?? 40.7128,
            'longitude' => $this->city->longitude ?? -74.0060,
            'fuzzed_latitude' => ($this->city->latitude ?? 40.7128) + 0.003,
            'fuzzed_longitude' => ($this->city->longitude ?? -74.0060) + 0.003,
            'neighborhood' => 'Downtown Arts District',
        ]);

        ListingContact::create([
            'listing_id' => $this->activeListing->id,
            'allow_platform_messages' => true,
            'allow_email_relay' => true,
            'masked_relay_email' => 'relay-abc12345@reply.localdatehub.com',
            'show_phone' => true,
            'preferred_phone_encrypted' => '+1 (555) 234-5678',
            'external_handle_encrypted' => '@adventurous_alex',
            'contact_instructions' => 'Please message with your favorite weekend activity.',
        ]);
    }

    public function test_listing_show_page_renders_with_details_and_breadcrumbs(): void
    {
        $response = $this->get(route('listings.show', $this->activeListing->slug));

        $response->assertStatus(200);
        $response->assertSee($this->activeListing->title);
        $response->assertSee('Advertiser Profile');
        $response->assertSee('Send Protected Message');
        $response->assertSee('relay-abc12345@reply.localdatehub.com');
        $response->assertSee('+1 (555) 234-5678');
        $response->assertSee('@adventurous_alex');
        $response->assertSee('Downtown Arts District');
        $response->assertSee('GPS coordinates are fuzzed');
    }

    public function test_listing_show_page_displays_similar_listings_in_same_area(): void
    {
        $similar = Listing::create([
            'user_id' => $this->advertiser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Another Local Connection in Same City',
            'slug' => 'another-local-connection-' . uniqid(),
            'description' => 'Looking for fun social outings.',
            'content_hash' => hash('sha256', 'another local' . uniqid()),
            'poster_age' => 31,
            'poster_gender' => 'Male',
            'intent' => 'activity_partner',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->get(route('listings.show', $this->activeListing->slug));

        $response->assertStatus(200);
        $response->assertSee('Similar Listings in');
        $response->assertSee($similar->title);
    }

    public function test_submit_protected_inquiry_encrypts_data_and_increments_reply_count(): void
    {
        $this->assertEquals(0, $this->activeListing->reply_count);

        $payload = [
            'name' => 'Jordan Seeker',
            'email' => 'jordan@example.com',
            'phone' => '+1 (555) 987-6543',
            'message' => 'Hi Alex! Loved your profile. Would love to grab coffee this weekend downtown.',
        ];

        $response = $this->actingAs($this->seeker)
            ->post(route('listings.contact.submit', $this->activeListing->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(1, $this->activeListing->fresh()->reply_count);

        $message = ContactMessage::where('listing_id', $this->activeListing->id)->latest()->first();
        $this->assertNotNull($message);
        $this->assertEquals($this->advertiser->id, $message->recipient_id);
        $this->assertEquals($this->seeker->id, $message->sender_id);
        $this->assertEquals('Jordan Seeker', $message->sender_name);
        // Decrypted via encrypted model attribute cast
        $this->assertEquals('jordan@example.com', $message->sender_email_encrypted);
        $this->assertEquals('+1 (555) 987-6543', $message->sender_phone_encrypted);
        $this->assertEquals($payload['message'], $message->message_encrypted);
        $this->assertFalse($message->is_read);
        $this->assertFalse($message->is_replied);
    }

    public function test_inquiry_with_honeypot_filled_is_silently_discarded(): void
    {
        $payload = [
            'name' => 'Spambot 9000',
            'email' => 'bot@spammer.org',
            'message' => 'Visit my web portal now!',
            'contact_hp' => 'I am a hidden honeypot spam bot',
        ];

        $response = $this->post(route('listings.contact.submit', $this->activeListing->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('contact_messages', [
            'sender_name' => 'Spambot 9000',
        ]);
        $this->assertEquals(0, $this->activeListing->fresh()->reply_count);
    }

    public function test_inquiry_with_prohibited_commercial_sex_work_is_blocked(): void
    {
        $payload = [
            'name' => 'Bad Actor',
            'email' => 'badactor@example.com',
            'message' => 'Hey, are you an escort? What is your ppm and hourly rate for incall?',
        ];

        $response = $this->from(route('listings.show', $this->activeListing->slug))
            ->post(route('listings.contact.submit', $this->activeListing->id), $payload);

        $response->assertRedirect(route('listings.show', $this->activeListing->slug));
        $response->assertSessionHasErrors('message');

        $this->assertDatabaseMissing('contact_messages', [
            'sender_name' => 'Bad Actor',
        ]);
        $this->assertEquals(0, $this->activeListing->fresh()->reply_count);
    }

    public function test_inquiry_with_financial_scam_is_blocked(): void
    {
        $payload = [
            'name' => 'Scam Artist',
            'email' => 'scam@example.com',
            'message' => 'I want to send you money through western union or crypto if you wire transfer advance fee first.',
        ];

        $response = $this->from(route('listings.show', $this->activeListing->slug))
            ->post(route('listings.contact.submit', $this->activeListing->id), $payload);

        $response->assertRedirect(route('listings.show', $this->activeListing->slug));
        $response->assertSessionHasErrors('message');

        $this->assertDatabaseMissing('contact_messages', [
            'sender_name' => 'Scam Artist',
        ]);
    }

    public function test_inquiry_cannot_be_submitted_to_inactive_listing(): void
    {
        $this->activeListing->update(['status' => Listing::STATUS_EXPIRED]);

        $payload = [
            'name' => 'Visitor',
            'email' => 'visitor@example.com',
            'message' => 'Is this still available?',
        ];

        $response = $this->post(route('listings.contact.submit', $this->activeListing->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors('message');
        $this->assertDatabaseMissing('contact_messages', ['sender_name' => 'Visitor']);
    }

    public function test_inquiry_cannot_be_submitted_if_advertiser_disabled_platform_messages(): void
    {
        $this->activeListing->contact->update(['allow_platform_messages' => false]);

        $payload = [
            'name' => 'Visitor',
            'email' => 'visitor@example.com',
            'message' => 'Trying to message you.',
        ];

        $response = $this->post(route('listings.contact.submit', $this->activeListing->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors('message');
        $this->assertDatabaseMissing('contact_messages', ['sender_name' => 'Visitor']);
    }

    public function test_submit_safety_report_creates_moderation_record(): void
    {
        $payload = [
            'report_reason' => 'fraud_scam',
            'report_details' => 'This listing appears to be using stolen photographs from an Instagram influencer.',
            'reporter_name' => 'Concerned User',
            'reporter_email' => 'concerned@example.com',
        ];

        $response = $this->actingAs($this->seeker)
            ->post(route('listings.report.submit', $this->activeListing->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report = ContactMessage::where('inquiry_type', 'safety_report')
            ->where('listing_id', $this->activeListing->id)
            ->first();

        $this->assertNotNull($report);
        $this->assertTrue($report->is_flagged);
        $this->assertEquals('fraud_scam', $report->flagged_reason);
        $this->assertEquals('Concerned User', $report->sender_name);
        $this->assertEquals('concerned@example.com', $report->sender_email_encrypted);
        $this->assertEquals($payload['report_details'], $report->message_encrypted);
    }

    public function test_high_severity_safety_report_auto_escalates_and_places_listing_in_pending_review(): void
    {
        $this->assertEquals(Listing::STATUS_ACTIVE, $this->activeListing->status);

        $payload = [
            'report_reason' => 'commercial_sex',
            'report_details' => 'Poster is soliciting paid sex services off-platform.',
            'reporter_name' => 'Community Watchdog',
        ];

        $response = $this->post(route('listings.report.submit', $this->activeListing->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(Listing::STATUS_PENDING_REVIEW, $this->activeListing->fresh()->status);
    }

    public function test_authenticated_user_can_toggle_favorite_listing(): void
    {
        $this->assertEquals(0, $this->activeListing->favorite_count);
        $this->assertFalse($this->seeker->favorites()->where('listing_id', $this->activeListing->id)->exists());

        // Toggle on
        $response = $this->actingAs($this->seeker)
            ->post(route('listings.favorite.toggle', $this->activeListing->id));

        $response->assertRedirect();
        $this->assertEquals(1, $this->activeListing->fresh()->favorite_count);
        $this->assertTrue($this->seeker->favorites()->where('listing_id', $this->activeListing->id)->exists());

        // Toggle off
        $response2 = $this->actingAs($this->seeker)
            ->post(route('listings.favorite.toggle', $this->activeListing->id));

        $response2->assertRedirect();
        $this->assertEquals(0, $this->activeListing->fresh()->favorite_count);
        $this->assertFalse($this->seeker->favorites()->where('listing_id', $this->activeListing->id)->exists());
    }

    public function test_unauthenticated_user_cannot_favorite_listing(): void
    {
        $response = $this->post(route('listings.favorite.toggle', $this->activeListing->id));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_private_inbox_and_see_inquiries_on_their_listings(): void
    {
        // Create inquiry for advertiser
        $msg = ContactMessage::create([
            'listing_id' => $this->activeListing->id,
            'recipient_id' => $this->advertiser->id,
            'sender_id' => $this->seeker->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Jordan Seeker',
            'sender_email_encrypted' => 'jordan@example.com',
            'subject' => 'Inquiry regarding listing',
            'message_encrypted' => 'I would love to meet up for coffee!',
            'is_read' => false,
            'is_replied' => false,
            'is_flagged' => false,
        ]);

        $response = $this->actingAs($this->advertiser)
            ->get(route('account.messages.index'));

        $response->assertStatus(200);
        $response->assertSee('Messages & Listing Inquiries', false);
        $response->assertSee('Jordan Seeker');
        $response->assertSee('I would love to meet up for coffee!');
        $response->assertSee('1 Unread');
    }

    public function test_user_cannot_view_another_users_private_inquiry(): void
    {
        $msg = ContactMessage::create([
            'listing_id' => $this->activeListing->id,
            'recipient_id' => $this->advertiser->id,
            'sender_id' => null,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Secret Admirer',
            'sender_email_encrypted' => 'secret@example.com',
            'subject' => 'Private secret inquiry',
            'message_encrypted' => 'Very private message.',
        ]);

        // Seeker attempts to access Advertiser's inquiry
        $response = $this->actingAs($this->seeker)
            ->get(route('account.messages.show', $msg->id));

        $response->assertStatus(403);
    }

    public function test_viewing_inquiry_marks_it_as_read_with_timestamp(): void
    {
        $msg = ContactMessage::create([
            'listing_id' => $this->activeListing->id,
            'recipient_id' => $this->advertiser->id,
            'sender_id' => $this->seeker->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Jordan Seeker',
            'sender_email_encrypted' => 'jordan@example.com',
            'subject' => 'Inquiry subject',
            'message_encrypted' => 'Decrypted text content here.',
            'is_read' => false,
        ]);

        $this->assertFalse($msg->is_read);
        $this->assertNull($msg->read_at);

        $response = $this->actingAs($this->advertiser)
            ->get(route('account.messages.show', $msg->id));

        $response->assertStatus(200);
        $response->assertSee('Decrypted text content here.');
        $response->assertSee('jordan@example.com');

        $this->assertTrue($msg->fresh()->is_read);
        $this->assertNotNull($msg->fresh()->read_at);
    }

    public function test_user_can_mark_inquiry_as_replied(): void
    {
        $msg = ContactMessage::create([
            'listing_id' => $this->activeListing->id,
            'recipient_id' => $this->advertiser->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Jordan Seeker',
            'sender_email_encrypted' => 'jordan@example.com',
            'message_encrypted' => 'Hello!',
            'is_replied' => false,
        ]);

        $response = $this->actingAs($this->advertiser)
            ->post(route('account.messages.replied', $msg->id));

        $response->assertRedirect();
        $this->assertTrue($msg->fresh()->is_replied);
    }

    public function test_user_can_flag_inquiry_as_spam(): void
    {
        $msg = ContactMessage::create([
            'listing_id' => $this->activeListing->id,
            'recipient_id' => $this->advertiser->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Annoying Bot',
            'sender_email_encrypted' => 'bot@example.com',
            'message_encrypted' => 'Spam content',
            'is_flagged' => false,
        ]);

        $response = $this->actingAs($this->advertiser)
            ->post(route('account.messages.flag', $msg->id), [
                'reason' => 'spam',
            ]);

        $response->assertRedirect();
        $this->assertTrue($msg->fresh()->is_flagged);
        $this->assertEquals('spam', $msg->fresh()->flagged_reason);
    }

    public function test_user_can_delete_inquiry_from_inbox(): void
    {
        $msg = ContactMessage::create([
            'listing_id' => $this->activeListing->id,
            'recipient_id' => $this->advertiser->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Jordan Seeker',
            'sender_email_encrypted' => 'jordan@example.com',
            'message_encrypted' => 'Hello!',
        ]);

        $response = $this->actingAs($this->advertiser)
            ->delete(route('account.messages.destroy', $msg->id));

        $response->assertRedirect(route('account.messages.index'));
        $this->assertSoftDeleted('contact_messages', ['id' => $msg->id]);
    }

    public function test_admin_can_access_moderation_queue_and_dismiss_flag(): void
    {
        $msg = ContactMessage::create([
            'listing_id' => $this->activeListing->id,
            'recipient_id' => $this->advertiser->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Questionable Sender',
            'sender_email_encrypted' => 'sender@example.com',
            'message_encrypted' => 'Suspicious communication.',
            'is_flagged' => true,
            'flagged_reason' => 'spam',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.moderation.messages.queue'));

        $response->assertStatus(200);
        $response->assertSee('Trust & Safety Moderation Queue', false);
        $response->assertSee('Questionable Sender');

        // Dismiss flag
        $dismissResponse = $this->actingAs($this->admin)
            ->post(route('admin.moderation.messages.dismiss', $msg->id));

        $dismissResponse->assertRedirect();
        $this->assertFalse($msg->fresh()->is_flagged);
        $this->assertNull($msg->fresh()->flagged_reason);
    }

    public function test_admin_can_suspend_listing_from_moderation_queue(): void
    {
        $msg = ContactMessage::create([
            'listing_id' => $this->activeListing->id,
            'recipient_id' => $this->advertiser->id,
            'inquiry_type' => 'safety_report',
            'sender_name' => 'Reporter',
            'sender_email_encrypted' => 'reporter@example.com',
            'message_encrypted' => 'Major safety breach.',
            'is_flagged' => true,
            'flagged_reason' => 'commercial_sex',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.moderation.messages.action', $msg->id), [
                'action' => 'suspend_listing',
                'note' => 'Immediate suspension for commercial solicitation.',
            ]);

        $response->assertRedirect();
        $this->assertEquals(Listing::STATUS_SUSPENDED, $this->activeListing->fresh()->status);
        $this->assertFalse($msg->fresh()->is_flagged);
    }
}
