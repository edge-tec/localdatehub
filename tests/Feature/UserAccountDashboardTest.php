<?php

namespace Tests\Feature;

use App\Livewire\AccountDashboard;
use App\Livewire\Admin\AdminEnterpriseHub;
use App\Models\User;
use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Accounts\Models\UserBlock;
use App\Modules\Accounts\Models\UserProfile;
use App\Modules\Billing\Models\FeaturedListing;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Categories\Models\Category;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UserAccountDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Category $category;
    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->category = Category::where('is_active', true)->first();
        $this->city = City::first();

        $this->user = User::factory()->create([
            'name' => 'Alex Member',
            'email' => 'alex@example.com',
            'password' => Hash::make('password123'),
            'account_status' => 'active',
            'is_age_verified' => false,
        ]);

        $this->otherUser = User::factory()->create([
            'name' => 'Taylor User',
            'email' => 'taylor@example.com',
            'account_status' => 'active',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_dashboard_and_is_redirected_to_login(): void
    {
        $response = $this->get(route('account.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_dashboard_overview_with_real_metrics(): void
    {
        // Create sample listings
        Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Active Coffee Date Ad',
            'slug' => 'active-coffee-date-' . uniqid(),
            'description' => 'Looking for coffee.',
            'content_hash' => hash('sha256', 'hash1' . uniqid()),
            'poster_age' => 28,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'view_count' => 125,
            'reply_count' => 8,
            'favorite_count' => 5,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->user)->get(route('account.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Alex Member');
        $response->assertSee('Active Coffee Date Ad');
        $response->assertSee('Overview');
        $response->assertSee('My Listings');
        $response->assertSee('Saved Listings');
    }

    public function test_dashboard_renders_my_listings_with_search_and_status_filtering(): void
    {
        $activeAd = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Hiking Companion in Downtown',
            'slug' => 'hiking-companion-' . uniqid(),
            'description' => 'Looking for outdoor hiking friends.',
            'content_hash' => hash('sha256', 'hash2' . uniqid()),
            'poster_age' => 30,
            'poster_gender' => 'Male',
            'intent' => 'activity_partner',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $pendingAd = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Pending Review Coffee Date',
            'slug' => 'pending-review-coffee-' . uniqid(),
            'description' => 'Under moderation review.',
            'content_hash' => hash('sha256', 'hash3' . uniqid()),
            'poster_age' => 25,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_PENDING_REVIEW,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'listings'])
            ->assertSee('Hiking Companion in Downtown')
            ->assertSee('Pending Review Coffee Date')
            ->set('listingStatus', 'active')
            ->assertSee('Hiking Companion in Downtown')
            ->assertDontSee('Pending Review Coffee Date')
            ->set('listingStatus', 'pending_review')
            ->assertSee('Pending Review Coffee Date')
            ->assertDontSee('Hiking Companion in Downtown')
            ->set('listingStatus', 'all')
            ->set('listingSearch', 'Hiking')
            ->assertSee('Hiking Companion in Downtown');
    }

    public function test_user_can_pause_and_resume_own_listing_from_dashboard(): void
    {
        $listing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Toggleable Listing Ad',
            'slug' => 'toggleable-listing-' . uniqid(),
            'description' => 'Listing to pause and resume.',
            'content_hash' => hash('sha256', 'hash4' . uniqid()),
            'poster_age' => 27,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'listings'])
            ->call('pauseListing', $listing->id);

        $this->assertEquals(Listing::STATUS_PAUSED, $listing->fresh()->status);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'listings'])
            ->call('resumeListing', $listing->id);

        $this->assertEquals(Listing::STATUS_ACTIVE, $listing->fresh()->status);
    }

    public function test_user_can_renew_expired_listing_from_dashboard(): void
    {
        $listing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Expired Listing to Renew',
            'slug' => 'expired-listing-' . uniqid(),
            'description' => 'Listing needing renewal.',
            'content_hash' => hash('sha256', 'hash5' . uniqid()),
            'poster_age' => 32,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_EXPIRED,
            'expires_at' => now()->subDay(),
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'listings'])
            ->call('renewListing', $listing->id);

        $listing->refresh();
        $this->assertEquals(Listing::STATUS_ACTIVE, $listing->status);
        $this->assertTrue($listing->expires_at->isFuture());
    }

    public function test_user_can_delete_own_listing_from_dashboard(): void
    {
        $listing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Listing to Delete',
            'slug' => 'listing-to-delete-' . uniqid(),
            'description' => 'Listing will be deleted.',
            'content_hash' => hash('sha256', 'hash6' . uniqid()),
            'poster_age' => 35,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'listings'])
            ->call('confirmDeleteListing', $listing->id)
            ->assertSet('showDeleteListingModal', true)
            ->call('deleteListing')
            ->assertSet('showDeleteListingModal', false);

        $this->assertSoftDeleted('listings', ['id' => $listing->id]);
    }

    public function test_user_cannot_modify_or_delete_another_users_listing(): void
    {
        $otherListing = Listing::create([
            'user_id' => $this->otherUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Other Users Private Ad',
            'slug' => 'other-user-ad-' . uniqid(),
            'description' => 'Belongs to other user.',
            'content_hash' => hash('sha256', 'hash7' . uniqid()),
            'poster_age' => 29,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class)
            ->call('pauseListing', $otherListing->id)
            ->assertStatus(404);

        $this->assertEquals(Listing::STATUS_ACTIVE, $otherListing->fresh()->status);
    }

    public function test_user_can_execute_bulk_actions_on_listings(): void
    {
        $ad1 = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Bulk Ad One',
            'slug' => 'bulk-ad-one-' . uniqid(),
            'description' => 'Ad 1',
            'content_hash' => hash('sha256', 'bulk1' . uniqid()),
            'poster_age' => 28,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        $ad2 = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Bulk Ad Two',
            'slug' => 'bulk-ad-two-' . uniqid(),
            'description' => 'Ad 2',
            'content_hash' => hash('sha256', 'bulk2' . uniqid()),
            'poster_age' => 29,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'listings'])
            ->set('selectedListings', [(string) $ad1->id, (string) $ad2->id])
            ->call('bulkPauseListings');

        $this->assertEquals(Listing::STATUS_PAUSED, $ad1->fresh()->status);
        $this->assertEquals(Listing::STATUS_PAUSED, $ad2->fresh()->status);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'listings'])
            ->set('selectedListings', [(string) $ad1->id, (string) $ad2->id])
            ->call('bulkResumeListings');

        $this->assertEquals(Listing::STATUS_ACTIVE, $ad1->fresh()->status);
        $this->assertEquals(Listing::STATUS_ACTIVE, $ad2->fresh()->status);
    }

    public function test_dashboard_renders_saved_listings_and_user_can_remove_favorite(): void
    {
        $favoriteListing = Listing::create([
            'user_id' => $this->otherUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Favorite Romance Ad',
            'slug' => 'favorite-romance-ad-' . uniqid(),
            'description' => 'A saved ad.',
            'content_hash' => hash('sha256', 'fav' . uniqid()),
            'poster_age' => 26,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'favorite_count' => 1,
        ]);

        $this->user->favorites()->attach($favoriteListing->id);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'saved'])
            ->assertSee('Favorite Romance Ad')
            ->call('removeFavorite', $favoriteListing->id);

        $this->assertFalse($this->user->favorites()->where('listing_id', $favoriteListing->id)->exists());
    }

    public function test_dashboard_renders_contact_messages_and_user_can_inspect_reply_flag_delete(): void
    {
        $myListing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Coffee Inquiry Target',
            'slug' => 'coffee-inquiry-target-' . uniqid(),
            'description' => 'Target ad for message.',
            'content_hash' => hash('sha256', 'msgtarget' . uniqid()),
            'poster_age' => 29,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        $message = ContactMessage::create([
            'listing_id' => $myListing->id,
            'recipient_id' => $this->user->id,
            'sender_id' => $this->otherUser->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Charlie Seeker',
            'sender_email_encrypted' => 'charlie@example.com',
            'subject' => 'Loved your profile!',
            'message_encrypted' => 'Would love to meet for tea this weekend.',
            'is_read' => false,
            'is_replied' => false,
            'is_flagged' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'messages'])
            ->assertSee('Charlie Seeker')
            ->call('inspectMessage', $message->id)
            ->assertSet('showMessageModal', true)
            ->call('markMessageReplied', $message->id)
            ->call('flagMessage', $message->id, 'spam');

        $message->refresh();
        $this->assertTrue($message->is_read);
        $this->assertTrue($message->is_replied);
        $this->assertTrue($message->is_flagged);
        $this->assertEquals('spam', $message->flagged_reason);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'messages'])
            ->call('deleteMessage', $message->id);

        $this->assertSoftDeleted('contact_messages', ['id' => $message->id]);
    }

    public function test_advanced_messaging_inbox_sent_direct_reply_and_bulk_actions(): void
    {
        $myListing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Advanced Messaging Listing',
            'slug' => 'adv-msg-listing-' . uniqid(),
            'description' => 'Target ad for testing advanced messaging.',
            'content_hash' => hash('sha256', 'advmsg' . uniqid()),
            'poster_age' => 28,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        $inboxMsg1 = ContactMessage::create([
            'listing_id' => $myListing->id,
            'recipient_id' => $this->user->id,
            'sender_id' => $this->otherUser->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Alice Buyer',
            'sender_email_encrypted' => 'alice@example.com',
            'subject' => 'Is this still open?',
            'message_encrypted' => 'Hello, I saw your ad and wanted to reach out.',
            'is_read' => false,
            'is_replied' => false,
            'is_flagged' => false,
        ]);

        $inboxMsg2 = ContactMessage::create([
            'listing_id' => $myListing->id,
            'recipient_id' => $this->user->id,
            'sender_id' => null,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => 'Bob Inquirer',
            'sender_email_encrypted' => 'bob@example.com',
            'subject' => 'Quick query',
            'message_encrypted' => 'Are you free tomorrow?',
            'is_read' => false,
            'is_replied' => false,
            'is_flagged' => false,
        ]);

        // Test Livewire component with folder toggling, direct reply, quick reply template, and bulk actions
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'messages'])
            ->assertSee('Alice Buyer')
            ->assertSee('Bob Inquirer')
            ->call('inspectMessage', $inboxMsg1->id)
            ->assertSet('showMessageModal', true)
            ->call('insertQuickReply', 'Hi, thanks for reaching out! Yes, this listing is still active and available.')
            ->assertSet('replyMessageContent', 'Hi, thanks for reaching out! Yes, this listing is still active and available.')
            ->call('sendDirectReply')
            ->assertHasNoErrors()
            // Verify reply created
            ->assertSet('replyMessageContent', '')
            // Test mark unread
            ->call('markMessageUnread', $inboxMsg1->id);

        $inboxMsg1->refresh();
        $this->assertFalse($inboxMsg1->is_read);

        // Verify direct reply was stored in contact_messages
        $this->assertDatabaseHas('contact_messages', [
            'listing_id' => $myListing->id,
            'sender_id' => $this->user->id,
            'recipient_id' => $this->otherUser->id,
            'is_replied' => true,
        ]);

        // Test bulk actions
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'messages'])
            ->set('selectedMessages', [$inboxMsg1->id, $inboxMsg2->id])
            ->call('bulkMarkRead');

        $inboxMsg1->refresh();
        $inboxMsg2->refresh();
        $this->assertTrue($inboxMsg1->is_read);
        $this->assertTrue($inboxMsg2->is_read);

        // Bulk mark unread
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'messages'])
            ->set('selectedMessages', [$inboxMsg1->id, $inboxMsg2->id])
            ->call('bulkMarkUnread');

        $inboxMsg1->refresh();
        $inboxMsg2->refresh();
        $this->assertFalse($inboxMsg1->is_read);
        $this->assertFalse($inboxMsg2->is_read);

        // Test folder toggle to 'sent'
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'messages'])
            ->set('messageFolder', 'sent')
            ->assertSee('Taylor User'); // recipient user name in sent folder

        // Test block sender
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'messages'])
            ->call('blockSenderFromMessage', $inboxMsg1->id);

        $this->assertDatabaseHas('user_blocks', [
            'blocker_id' => $this->user->id,
            'blocked_id' => $this->otherUser->id,
        ]);
    }

    public function test_dashboard_renders_listing_views_and_analytics_breakdown(): void
    {
        Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'High Performance Classified Ad',
            'slug' => 'high-perf-ad-' . uniqid(),
            'description' => 'High views ad.',
            'content_hash' => hash('sha256', 'perf' . uniqid()),
            'poster_age' => 31,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'view_count' => 540,
            'reply_count' => 18,
            'favorite_count' => 12,
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'analytics'])
            ->assertSee('Total Ad Impressions')
            ->assertSee('High Performance Classified Ad')
            ->assertSee('540');
    }

    public function test_dashboard_renders_featured_promotions_and_user_can_activate_featured_spotlight(): void
    {
        $plan = PricingPlan::where('plan_type', 'featured_spot')->first();

        $listing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Promote This Coffee Ad',
            'slug' => 'promote-coffee-ad-' . uniqid(),
            'description' => 'Ad to feature.',
            'content_hash' => hash('sha256', 'promote' . uniqid()),
            'poster_age' => 29,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'is_featured' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'promotions'])
            ->set('promoteListingId', $listing->id)
            ->set('selectedPlanId', $plan->id)
            ->set('selectedPlacement', 'category_top')
            ->call('activatePromotion')
            ->assertSet('showPromoteModal', false);

        $listing->refresh();
        $this->assertTrue($listing->is_featured);

        $featured = FeaturedListing::where('listing_id', $listing->id)->first();
        $this->assertNotNull($featured);
        $this->assertTrue($featured->ends_at->isFuture());

        $this->assertDatabaseHas('featured_listings', [
            'listing_id' => $listing->id,
            'user_id' => $this->user->id,
            'placement' => 'category_top',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => $this->user->id,
            'listing_id' => $listing->id,
            'status' => 'completed',
        ]);
    }

    public function test_dashboard_renders_subscription_status_and_user_can_subscribe_or_cancel(): void
    {
        $membershipPlan = PricingPlan::where('plan_type', 'membership')->first();

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'subscription'])
            ->call('subscribeToPlan', $membershipPlan->id);

        $sub = Subscription::where('user_id', $this->user->id)->where('status', 'active')->first();
        $this->assertNotNull($sub);
        $this->assertEquals($membershipPlan->id, $sub->pricing_plan_id);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'subscription'])
            ->call('cancelSubscription', $sub->id);

        $this->assertEquals('canceled', $sub->fresh()->status);
    }

    public function test_dashboard_renders_billing_history_ledger(): void
    {
        PaymentTransaction::create([
            'user_id' => $this->user->id,
            'reference_id' => 'TXN-TEST123456',
            'amount_cents' => 999,
            'currency' => 'USD',
            'payment_method' => 'card',
            'gateway' => 'stripe',
            'status' => 'completed',
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'billing'])
            ->assertSee('TXN-TEST123456')
            ->assertSee('$9.99')
            ->assertSee('completed');
    }

    public function test_user_can_update_profile_settings(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'profile'])
            ->set('displayName', 'Alex Updated')
            ->set('bio', 'My updated personal bio details.')
            ->set('city', 'Austin')
            ->set('stateCode', 'TX')
            ->call('saveProfile');

        $this->assertEquals('Alex Updated', $this->user->fresh()->name);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'display_name' => 'Alex Updated',
            'city' => 'Austin',
            'state_code' => 'TX',
        ]);
    }

    public function test_user_can_update_privacy_and_anti_triangulation_settings(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'privacy'])
            ->set('hideLocationDetails', true)
            ->set('hideAge', true)
            ->set('requireVerifiedSenders', true)
            ->call('savePrivacySettings');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'hide_location_details' => 1,
            'hide_age' => 1,
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'require_verified_senders' => 1,
        ]);
    }

    public function test_user_can_submit_age_assurance_affirmation_and_become_18_plus_verified(): void
    {
        $this->assertFalse($this->user->is_age_verified);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'age_assurance'])
            ->set('declaredBirthdate', '1995-06-15')
            ->set('ageAffirmation', true)
            ->call('submitAgeAssurance');

        $this->assertTrue($this->user->fresh()->is_age_verified);
        $this->assertDatabaseHas('age_assurances', [
            'user_id' => $this->user->id,
            'status' => 'verified',
        ]);
    }

    public function test_age_assurance_rejects_under_18_date_of_birth(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'age_assurance'])
            ->set('declaredBirthdate', now()->subYears(16)->format('Y-m-d'))
            ->set('ageAffirmation', true)
            ->call('submitAgeAssurance')
            ->assertHasErrors(['declaredBirthdate']);

        $this->assertFalse($this->user->fresh()->is_age_verified);
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'security'])
            ->set('currentPassword', 'password123')
            ->set('newPassword', 'newsupersecret123')
            ->set('newPasswordConfirmation', 'newsupersecret123')
            ->call('updatePassword');

        $this->assertTrue(Hash::check('newsupersecret123', $this->user->fresh()->password));
    }

    public function test_user_can_block_and_unblock_other_users(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'blocked_users'])
            ->set('blockUsername', 'Taylor User')
            ->set('blockReason', 'Unwanted messages')
            ->call('blockUser');

        $block = UserBlock::where('blocker_id', $this->user->id)->where('blocked_id', $this->otherUser->id)->first();
        $this->assertNotNull($block);
        $this->assertEquals('Unwanted messages', $block->reason);

        // Unblock
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'blocked_users'])
            ->call('unblockUser', $block->id);

        $this->assertDatabaseMissing('user_blocks', ['id' => $block->id]);
    }

    public function test_user_cannot_block_themselves(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'blocked_users'])
            ->set('blockUsername', 'Alex Member')
            ->call('blockUser')
            ->assertHasErrors(['blockUsername']);
    }

    public function test_user_can_delete_their_account_with_valid_password_confirmation(): void
    {
        $listing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Ad to be purged on account deletion',
            'slug' => 'purge-ad-' . uniqid(),
            'description' => 'Will be purged.',
            'content_hash' => hash('sha256', 'purge' . uniqid()),
            'poster_age' => 28,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'security'])
            ->set('deleteAccountPassword', 'password123')
            ->call('deleteAccount');

        $this->assertSoftDeleted('users', ['id' => $this->user->id]);
        $this->assertSoftDeleted('listings', ['id' => $listing->id]);
        $this->assertGuest();
    }

    public function test_user_can_upload_and_remove_profile_photo(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('profile-alex.jpg', 400, 400);

        // Upload photo via uploadAvatarNow
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'profile'])
            ->set('avatar', $photo)
            ->call('uploadAvatarNow')
            ->assertHasNoErrors();

        $freshUser = $this->user->fresh();
        $this->assertNotNull($freshUser->avatar);
        $this->assertNotNull($freshUser->profile->avatar_path);
        $this->assertNotNull($freshUser->avatar_url);
        Storage::disk('public')->assertExists($freshUser->avatar);

        // Remove photo
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'profile'])
            ->call('removeAvatar')
            ->assertHasNoErrors();

        $freshUser = $this->user->fresh();
        $this->assertNull($freshUser->avatar);
        $this->assertNull($freshUser->profile->avatar_path);
        $this->assertNull($freshUser->avatar_url);
    }

    public function test_admin_can_update_and_remove_user_profile_photo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $targetUser = User::factory()->create([
            'role' => 'user',
            'name' => 'Member Need Photo',
            'email' => 'target@example.com',
            'account_status' => 'active',
        ]);

        $photo = UploadedFile::fake()->image('admin-uploaded.png', 300, 300);

        // Admin uploads a profile photo for the target user
        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['tab' => 'users'])
            ->call('openEditUserModal', $targetUser->id)
            ->assertSet('editUserId', $targetUser->id)
            ->set('editUserAvatar', $photo)
            ->call('updateUser')
            ->assertHasNoErrors();

        $freshTarget = $targetUser->fresh();
        $this->assertNotNull($freshTarget->avatar);
        $this->assertNotNull($freshTarget->profile->avatar_path);
        Storage::disk('public')->assertExists($freshTarget->avatar);

        // Admin removes the profile photo of the target user
        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['tab' => 'users'])
            ->call('openEditUserModal', $targetUser->id)
            ->call('removeUserAvatarInEditModal')
            ->assertHasNoErrors();

        $freshTarget = $targetUser->fresh();
        $this->assertNull($freshTarget->avatar);
        $this->assertNull($freshTarget->profile->avatar_path);
    }

    public function test_user_report_submission_syncs_in_realtime_to_admin_reports_and_can_be_deleted(): void
    {
        $listing = Listing::create([
            'user_id' => $this->otherUser->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Suspicious Ad Needing Report',
            'slug' => 'suspicious-ad-' . uniqid(),
            'description' => 'Suspicious description.',
            'content_hash' => hash('sha256', 'suspicious-' . uniqid()),
            'poster_age' => 25,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user)->post(route('listings.report.submit', $listing->id), [
            'report_reason' => 'fraud_scam',
            'report_details' => 'This advertiser asked for a wire transfer before meeting in person.',
            'reporter_name' => $this->user->name,
            'reporter_email' => $this->user->email,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('content_reports', [
            'reportable_type' => Listing::class,
            'reportable_id' => $listing->id,
            'reason_code' => 'fraud_scam',
            'status' => 'open',
            'risk_level' => 'high',
        ]);

        $report = \App\Modules\Moderation\Models\ContentReport::where('reportable_id', $listing->id)->latest('id')->first();
        $this->assertNotNull($report);

        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'reports'])
            ->assertSee('This advertiser asked for a wire transfer before meeting in person.')
            ->call('deleteReport', $report->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('content_reports', ['id' => $report->id]);
    }

    public function test_enterprise_security_headers_are_enforced(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $this->assertStringContainsString("frame-ancestors 'self'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_when_user_or_admin_checks_notification_or_message_it_is_marked_read_seen(): void
    {
        $listing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Listing With Inquiry & Notification',
            'slug' => 'listing-inquiry-notif-' . uniqid(),
            'description' => 'Testing read/seen states.',
            'content_hash' => hash('sha256', 'inquiry-' . uniqid()),
            'poster_age' => 27,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
        ]);

        $message = ContactMessage::create([
            'listing_id' => $listing->id,
            'sender_id' => $this->otherUser->id,
            'recipient_id' => $this->user->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => $this->otherUser->name,
            'sender_email_encrypted' => $this->otherUser->email,
            'subject' => 'Interested in your listing',
            'message_encrypted' => 'Hello, I saw your listing and would love to connect!',
            'sender_ip' => '127.0.0.1',
            'is_read' => false,
            'is_flagged' => true,
        ]);

        $notification = \App\Modules\Notifications\Models\NotificationLog::create([
            'user_id' => $this->user->id,
            'channel' => 'database',
            'notification_type' => 'contact_received',
            'recipient_target' => $this->user->email,
            'subject' => 'New Inquiry Received',
            'message' => 'Hello, I saw your listing and would love to connect!',
            'status' => 'sent',
            'is_read' => false,
            'read_at' => null,
            'sent_at' => now(),
        ]);

        // 1. User checks notification in AccountDashboard -> marked read/seen
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'notifications'])
            ->call('inspectNotification', $notification->id)
            ->assertSet('showNotificationModal', true);

        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);

        // 2. User checks message in AccountDashboard -> marked read/seen
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'messages'])
            ->call('inspectMessage', $message->id)
            ->assertSet('showMessageModal', true);

        $message->refresh();
        $this->assertTrue($message->is_read);
        $this->assertNotNull($message->read_at);

        // 3. Admin checks report & notification in AdminEnterpriseHub -> marked read/seen
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $report = \App\Modules\Moderation\Models\ContentReport::create([
            'reporter_user_id' => $this->otherUser->id,
            'reportable_type' => Listing::class,
            'reportable_id' => $listing->id,
            'reason_code' => 'spam_duplicate',
            'details' => 'Checking if admin inspection marks report as seen.',
            'status' => 'open',
            'risk_level' => 'medium',
            'is_read' => false,
            'read_at' => null,
        ]);

        $adminNotif = \App\Modules\Notifications\Models\NotificationLog::create([
            'user_id' => $this->otherUser->id,
            'channel' => 'database',
            'notification_type' => 'admin_broadcast',
            'recipient_target' => $this->otherUser->email,
            'subject' => 'System Broadcast Check',
            'message' => 'Broadcast message body.',
            'status' => 'sent',
            'is_read' => false,
            'read_at' => null,
            'sent_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'reports'])
            ->call('openInspectReportModal', $report->id)
            ->assertSet('showInspectReportModal', true)
            ->call('inspectNotification', $adminNotif->id)
            ->assertSet('showInspectNotificationModal', true);

        $report->refresh();
        $adminNotif->refresh();
        $this->assertTrue($report->is_read);
        $this->assertNotNull($report->read_at);
        $this->assertTrue($adminNotif->is_read);
        $this->assertNotNull($adminNotif->read_at);
    }

    public function test_admin_can_upload_logo_and_favicon_images_and_apply_across_platform(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $logoFile = UploadedFile::fake()->image('brand-logo.png', 240, 60);
        $darkLogoFile = UploadedFile::fake()->image('brand-logo-dark.png', 240, 60);
        $mobileLogoFile = UploadedFile::fake()->image('brand-mobile.png', 64, 64);
        $faviconFile = UploadedFile::fake()->image('brand-favicon.png', 32, 32);

        $component = Livewire::actingAs($admin)
            ->test(AdminEnterpriseHub::class, ['initialTab' => 'settings'])
            ->set('settingsSubTab', 'branding')
            ->set('brandLogoUpload', $logoFile)
            ->set('brandLogoDarkUpload', $darkLogoFile)
            ->set('brandMobileLogoUpload', $mobileLogoFile)
            ->set('brandFaviconUpload', $faviconFile);

        $savedLogoUrl = \App\Modules\Admin\Models\SystemSetting::where('key', 'site_logo_url')->value('value');
        $savedDarkLogoUrl = \App\Modules\Admin\Models\SystemSetting::where('key', 'site_logo_dark_url')->value('value');
        $savedMobileLogoUrl = \App\Modules\Admin\Models\SystemSetting::where('key', 'site_mobile_logo_url')->value('value');
        $savedFaviconUrl = \App\Modules\Admin\Models\SystemSetting::where('key', 'site_favicon_url')->value('value');

        $this->assertNotNull($savedLogoUrl);
        $this->assertStringStartsWith('/storage/branding/', $savedLogoUrl);
        $this->assertStringStartsWith('/storage/branding/', $savedDarkLogoUrl);
        $this->assertStringStartsWith('/storage/branding/', $savedMobileLogoUrl);
        $this->assertStringStartsWith('/storage/branding/', $savedFaviconUrl);

        Storage::disk('public')->assertExists(str_replace('/storage/', '', $savedLogoUrl));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $savedDarkLogoUrl));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $savedMobileLogoUrl));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $savedFaviconUrl));

        // Verify resetBrandAsset works
        $component->call('resetBrandAsset', 'site_favicon_url');
        $this->assertEquals('/favicon.ico', \App\Modules\Admin\Models\SystemSetting::where('key', 'site_favicon_url')->value('value'));
    }

    public function test_user_can_toggle_email_between_public_and_encrypted_on_posts(): void
    {
        $listing = Listing::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'state_id' => $this->city->state_id,
            'city_id' => $this->city->id,
            'title' => 'Email Visibility Test Listing',
            'slug' => 'email-visibility-test-' . uniqid(),
            'description' => 'Testing public vs encrypted email visibility on post.',
            'content_hash' => hash('sha256', 'email-vis-' . uniqid()),
            'poster_age' => 28,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        \App\Modules\Listings\Models\ListingContact::create([
            'listing_id' => $listing->id,
            'allow_platform_messages' => true,
            'allow_email_relay' => true,
            'show_email' => false,
            'masked_relay_email' => 'relay-secret999@localdatehub.com',
            'preferred_email_encrypted' => $this->user->email,
        ]);

        // 1. By default (Encrypted mode): post shows masked relay email and hides real email
        $responseEncrypted = $this->get(route('listings.show', $listing->slug));
        $responseEncrypted->assertStatus(200);
        $responseEncrypted->assertSee('relay-secret999@localdatehub.com');
        $responseEncrypted->assertDontSee('mailto:' . $this->user->email);

        // 2. User toggles email to Public from AccountDashboard
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'listings'])
            ->call('toggleListingEmailVisibility', $listing->id);

        $this->assertTrue($listing->contact()->first()->show_email);

        // 3. Now anyone viewing the post sees the Public Email directly
        $responsePublic = $this->get(route('listings.show', $listing->slug));
        $responsePublic->assertStatus(200);
        $responsePublic->assertSee($this->user->email);
        $responsePublic->assertSee('Direct Public Email:');

        // 4. User can switch back to Encrypted via Privacy Settings
        Livewire::actingAs($this->user)
            ->test(AccountDashboard::class, ['initialTab' => 'privacy'])
            ->set('showPublicEmail', false)
            ->call('savePrivacySettings');

        $this->assertFalse($listing->contact()->first()->show_email);
    }
}
