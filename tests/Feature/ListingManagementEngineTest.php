<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingMedia;
use App\Modules\Listings\Services\ListingManagementService;
use App\Modules\Listings\Services\ListingMediaService;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Support\Exceptions\AgeVerificationException;
use App\Support\Exceptions\ProhibitedContentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class ListingManagementEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_user_can_save_and_resume_listing_draft(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        // 1. Create initial draft
        $draftData = [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Initial Draft Title',
            'description' => 'Draft description text here.',
            'poster_age' => 30,
            'poster_gender' => 'Male',
            'intent' => 'dating',
            'is_autosaved' => true,
        ];

        $draft = $service->saveDraft($user, $draftData);

        $this->assertNotNull($draft);
        $this->assertEquals(Listing::STATUS_DRAFT, $draft->status);
        $this->assertTrue($draft->is_autosaved);
        $this->assertEquals('Initial Draft Title', $draft->title);

        // 2. Resume and update draft
        $updatedData = array_merge($draftData, [
            'title' => 'Updated Draft Title',
            'description' => 'Updated longer draft text here.',
        ]);

        $updatedDraft = $service->saveDraft($user, $updatedData, $draft->id);

        $this->assertEquals($draft->id, $updatedDraft->id);
        $this->assertEquals('Updated Draft Title', $updatedDraft->title);
    }

    public function test_prohibited_content_is_blocked_on_submission(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Dinner Companion with Hourly Rate',
            'description' => 'Looking for companionship, generous compensation required.',
            'poster_age' => 28,
        ]);

        $this->expectException(ProhibitedContentException::class);
        $service->submitForReview($listing, $user);
    }

    public function test_underage_poster_is_rejected(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Young person seeking friends',
            'description' => 'Looking for casual coffee and hangout.',
            'poster_age' => 17, // Under 18
        ]);

        $this->expectException(AgeVerificationException::class);
        $service->submitForReview($listing, $user);
    }

    public function test_duplicate_listing_detection_prevents_spam(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $title = 'Unique Coffee Meetup In Central Austin';
        $description = 'Looking for coffee and genuine intellectual conversation this Saturday.';

        // Create first listing and submit
        $listing1 = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => $title,
            'description' => $description,
            'poster_age' => 31,
            'attributes' => ['intent' => 'dating'],
        ]);
        $service->submitForReview($listing1, $user, ['attributes' => ['intent' => 'dating']]);

        // Attempting to submit another listing with identical content should fail duplicate check
        $listing2 = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => $title,
            'description' => $description,
            'poster_age' => 31,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $service->submitForReview($listing2, $user, ['attributes' => ['intent' => 'dating']]);
    }

    public function test_submission_for_review_moves_status_to_pending_review(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Genuine Long-Term Romantic Connection',
            'description' => 'Ready for an honest relationship and quality weekend dates.',
            'poster_age' => 33,
            'attributes' => ['intent' => 'long_term'],
        ]);

        $submitted = $service->submitForReview($listing, $user, ['attributes' => ['intent' => 'long_term']]);

        $this->assertEquals(Listing::STATUS_PENDING_REVIEW, $submitted->status);
        $this->assertDatabaseHas('listing_status_history', [
            'listing_id' => $listing->id,
            'to_status' => Listing::STATUS_PENDING_REVIEW,
            'changed_by_user_id' => $user->id,
        ]);
    }

    public function test_moderation_approval_workflow_publishes_listing(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $moderator = User::factory()->create(['role' => 'moderator', 'is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Adventure and Weekend Road Trips',
            'description' => 'Seeking an active partner for state parks, hiking, and live music.',
            'poster_age' => 29,
        ]);
        $service->submitForReview($listing, $user, ['attributes' => ['intent' => 'activity']]);

        // Moderator approves
        $approved = $service->approveListing($listing, $moderator);

        $this->assertEquals(Listing::STATUS_ACTIVE, $approved->status);
        $this->assertNotNull($approved->published_at);
        $this->assertNotNull($approved->expires_at);
        $this->assertTrue($approved->expires_at->isFuture());

        // Status history audit
        $this->assertDatabaseHas('listing_status_history', [
            'listing_id' => $listing->id,
            'to_status' => Listing::STATUS_ACTIVE,
            'changed_by_user_id' => $moderator->id,
        ]);
    }

    public function test_moderation_rejection_workflow_records_feedback(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $moderator = User::factory()->create(['role' => 'moderator', 'is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Weekend Coffee Meet',
            'description' => 'Coffee meet details here.',
            'poster_age' => 25,
        ]);
        $service->submitForReview($listing, $user, ['attributes' => ['intent' => 'dating']]);

        // Moderator rejects with reason
        $reason = 'Please provide a more descriptive paragraph about what you enjoy.';
        $rejected = $service->rejectListing($listing, $reason, $moderator);

        $this->assertEquals(Listing::STATUS_REJECTED, $rejected->status);
        $this->assertEquals($reason, $rejected->rejection_reason);
        $this->assertEquals(1, $rejected->rejection_count);
    }

    public function test_listing_renewal_extends_expiration_by_30_days(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Active Listing To Renew',
            'description' => 'Good description text here.',
            'poster_age' => 30,
        ]);
        $service->submitForReview($listing, $user, ['attributes' => ['intent' => 'dating']]);
        $service->approveListing($listing);

        // Manually set expires_at close to now
        $listing->update(['expires_at' => now()->addHour()]);

        $renewed = $service->renewListing($listing, $user);

        $this->assertEquals(Listing::STATUS_ACTIVE, $renewed->status);
        $this->assertNotNull($renewed->renewed_at);
        $this->assertTrue($renewed->expires_at->gt(now()->addDays(28)));
    }

    public function test_listing_pause_and_resume_lifecycle(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Listing To Pause',
            'description' => 'Some description here.',
            'poster_age' => 30,
        ]);
        $service->submitForReview($listing, $user, ['attributes' => ['intent' => 'dating']]);
        $service->approveListing($listing);

        // Pause
        $service->pauseListing($listing, $user);
        $listing->refresh();
        $this->assertEquals(Listing::STATUS_PAUSED, $listing->status);

        // Resume
        $service->resumeListing($listing, $user);
        $listing->refresh();
        $this->assertEquals(Listing::STATUS_ACTIVE, $listing->status);
    }

    public function test_listing_revisions_are_recorded_on_updates(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Initial Title Before Revision',
            'description' => 'Initial description before revision.',
            'poster_age' => 30,
        ]);

        $service->updateListing($listing, [
            'title' => 'Revised Title After Update',
            'description' => 'Revised description after update.',
        ], $user);

        $listing->refresh();
        $this->assertEquals('Revised Title After Update', $listing->title);
        $this->assertGreaterThanOrEqual(1, $listing->revisions()->count());
        $this->assertEquals('Initial Title Before Revision', $listing->revisions()->first()->title);
    }

    public function test_media_upload_validation_and_exif_sanitization(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $mediaService = app(ListingMediaService::class);
        $listingService = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $listingService->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Photo Test Ad',
            'description' => 'Testing photo upload and sanitization.',
            'poster_age' => 30,
        ]);

        // Upload valid test image
        $image = UploadedFile::fake()->image('test_photo.jpg', 800, 600);
        $media = $mediaService->processAndStorePhoto($listing, $image, true);

        $this->assertNotNull($media);
        $this->assertEquals('image/webp', $media->mime_type);
        $this->assertTrue($media->is_primary);
        $this->assertEquals('approved', $media->moderation_status);
        $this->assertTrue($listing->fresh()->has_photos);

        // Upload limit check
        for ($i = 0; $i < 5; $i++) {
            $mediaService->processAndStorePhoto($listing, UploadedFile::fake()->image("test_{$i}.png", 400, 400));
        }

        // 7th photo should throw exception (max 6)
        $this->expectException(InvalidArgumentException::class);
        $mediaService->processAndStorePhoto($listing, UploadedFile::fake()->image('exceed.jpg', 400, 400));
    }

    public function test_listing_ownership_authorization_policy(): void
    {
        $owner = User::factory()->create(['is_age_verified' => true]);
        $stranger = User::factory()->create(['is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $service->saveDraft($owner, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Owner Protected Listing',
            'description' => 'Owner only should be able to edit this.',
            'poster_age' => 30,
        ]);

        // Stranger attempting to access edit route gets 403
        $this->actingAs($stranger)->get("/listings/{$listing->id}/edit")->assertStatus(403);

        // Stranger attempting to delete gets 403
        $this->actingAs($stranger)->delete("/account/listings/{$listing->id}")->assertStatus(403);

        // Owner can access edit
        $this->actingAs($owner)->get("/listings/{$listing->id}/edit")->assertStatus(200);
    }

    public function test_public_listing_view_increments_analytics(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $service = app(ListingManagementService::class);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $city = City::first();

        $listing = $service->saveDraft($user, [
            'category_id' => $category->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Public Analytics Test Ad',
            'description' => 'Testing views analytics counter on show page.',
            'poster_age' => 28,
        ]);
        $service->submitForReview($listing, $user, ['attributes' => ['intent' => 'dating']]);
        $service->approveListing($listing);

        $initialViews = $listing->view_count;

        // Visit public page
        $this->get("/listings/{$listing->slug}")->assertStatus(200);

        $listing->refresh();
        $this->assertEquals($initialViews + 1, $listing->view_count);
        $this->assertDatabaseHas('listing_analytics', [
            'listing_id' => $listing->id,
            'views' => 1,
        ]);
    }

    public function test_livewire_listing_wizard_steps_and_submission(): void
    {
        $user = User::factory()->create(['is_age_verified' => true]);
        $category = Category::where('slug', 'men-seeking-women')->first();
        $group = $category->parent;
        $state = State::where('code', 'TX')->first() ?: State::first();
        $city = City::where('state_id', $state->id)->first();

        $comp = Livewire::actingAs($user)->test(\App\Livewire\ListingWizard::class);
        $comp->assertStatus(200);

        // Step 1: Group
        $comp->set('selectedGroupId', $group->id);
        $comp->call('nextStep');
        $comp->assertSet('currentStep', 2);

        // Step 2: Subcategory
        $comp->set('selectedCategoryId', $category->id);
        $comp->call('nextStep');
        $comp->assertSet('currentStep', 3);

        // Step 3: Title & Age
        $comp->set('title', 'Austin Professional Seeking Long Term Match');
        $comp->set('poster_age', 32);
        $comp->set('poster_gender', 'Male');
        $comp->set('intent', 'dating');
        $comp->call('nextStep');
        $comp->assertSet('currentStep', 4);

        // Step 4: Description
        $comp->set('description', 'I enjoy outdoor trail running, live indie music, and trying new local restaurants.');
        $comp->call('nextStep');
        $comp->assertSet('currentStep', 5);

        // Jump to Step 6: Location
        $comp->set('currentStep', 6);
        $comp->set('state_id', $state->id);
        $comp->set('city_id', $city->id);
        $comp->call('nextStep');
        $comp->assertSet('currentStep', 7);

        // Jump to Step 11: Policy confirmation
        $comp->set('currentStep', 11);
        $comp->set('confirm_age_18', true);
        $comp->set('confirm_community_rules', true);
        $comp->set('confirm_anti_trafficking', true);
        $comp->call('nextStep');
        $comp->assertSet('currentStep', 12);

        // Final submission
        $comp->call('submitForReview');
        $comp->assertRedirect('/account/listings');

        $this->assertDatabaseHas('listings', [
            'user_id' => $user->id,
            'title' => 'Austin Professional Seeking Long Term Match',
            'status' => Listing::STATUS_PENDING_REVIEW,
        ]);
    }
}
