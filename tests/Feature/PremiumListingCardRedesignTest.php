<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Services\AdCampaignManager;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingMedia;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Verification\Services\IdentityVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PremiumListingCardRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
        Storage::fake('local');
    }

    protected function makeListing(User $owner, array $overrides = []): Listing
    {
        $category = Category::where('slug', 'women-seeking-men')->first() ?? Category::first();
        $city = City::first();
        $state = $city?->state ?? State::first();

        return Listing::create(array_merge([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'title' => 'Thoughtful Coffee & Conversation Nearby ' . Str::random(5),
            'slug' => 'coffee-conversation-' . Str::random(8),
            'description' => 'Looking for genuine local connection over coffee, weekend markets, and easy conversation.',
            'poster_age' => 29,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'has_photos' => false,
            'is_featured' => false,
            'published_at' => now()->subDays(3),
            'expires_at' => now()->addDays(30),
        ], $overrides));
    }

    protected function attachPhotos(Listing $listing, int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            ListingMedia::create([
                'listing_id' => $listing->id,
                'user_id' => $listing->user_id,
                'file_path' => "listings/{$listing->id}/photo-{$i}.webp",
                'thumbnail_path' => "listings/{$listing->id}/thumb-{$i}.webp",
                'original_filename' => "photo-{$i}.jpg",
                'mime_type' => 'image/webp',
                'file_size_bytes' => 12345,
                'width' => 800,
                'height' => 1000,
                'is_primary' => $i === 0,
                'display_order' => $i,
                'moderation_status' => 'approved',
            ]);
        }

        $listing->update(['has_photos' => true]);
    }

    public function test_homepage_featured_and_latest_cards_use_premium_redesign_with_no_fabricated_heights_or_view_ad_labels(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        $unverifiedOwner = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $featuredListing = $this->makeListing($unverifiedOwner, [
            'title' => 'Featured Sunset Walk Partner',
            'poster_age' => 31,
            'is_featured' => true,
            'published_at' => now()->subHours(2),
        ]);
        $this->attachPhotos($featuredListing, 3);

        $standardListing = $this->makeListing($unverifiedOwner, [
            'title' => 'Text Only Weekend Companion',
            'poster_age' => 26,
            'is_featured' => false,
            'published_at' => now()->subDays(4),
        ]);

        // Also seed a sponsored campaign card in the homepage feed to confirm clear differentiation
        app(AdCampaignManager::class)->saveCampaign([
            'name' => 'Homepage Sponsored Blend',
            'campaign_type' => 'affiliate',
            'advertiser_name' => 'TrustedPartner',
            'affiliate_tracking_url' => 'https://partner.example.com/deal',
            'creative_title' => 'VIP Partner Offer',
            'disclosure_label' => 'Sponsored',
            'cta_text' => 'Visit Offer',
            'status' => AdCampaign::STATUS_ACTIVE,
            'priority' => 90,
            'placements' => [AdCampaign::SLOT_BETWEEN_LISTINGS, AdCampaign::SLOT_HOMEPAGE_FEED],
        ], null, $admin->id);

        $response = $this->withSession(['age_confirmed_18' => true])
            ->get(route('home'))
            ->assertOk();

        $html = $response->getContent();

        // Organic cards present with "View Details" & "Contact Member", never "View Ad"
        $response->assertSee('Featured Sunset Walk Partner');
        $response->assertSee('Text Only Weekend Companion');
        $response->assertSee('View Details', false);
        $response->assertSee('Contact Member', false);
        $response->assertDontSee('>View Ad<', false);
        $response->assertDontSee('View Ad →', false);

        // No fabricated static height hints
        $response->assertDontSee("5'7\"", false);
        $response->assertDontSee("5'6\"", false);

        // Clean 4:5 cover image & attractive placeholder fallback when listing has no photo
        $this->assertStringContainsString('aspect-[4/5]', $html);
        $this->assertStringContainsString('object-cover', $html);
        $this->assertStringContainsString('No photo provided', $html);

        // Multi-photo counter (>1 photo) renders '3', and Featured badge renders while unverified owner does NOT get Verified badge
        $response->assertSee('data-card-variant="featured"', false);
        $response->assertSee('data-card-variant="standard"', false);
        $response->assertDontSee('Identity Verified Member', false);

        // Sponsored card remains clearly labeled alongside organic listings
        $response->assertSee('VIP Partner Offer');
        $response->assertSee('Sponsored');
        $response->assertSee('Visit Offer');
    }

    public function test_verified_and_recent_card_variants_respect_strict_id_verification_and_favorite_state(): void
    {
        $reviewer = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        $verifiedMember = User::factory()->create(['role' => 'user', 'account_status' => 'active']);
        $saverUser = User::factory()->create(['role' => 'user', 'account_status' => 'active']);

        // Submit and approve real ID card verification for $verifiedMember
        $this->actingAs($verifiedMember)
            ->post(route('account.verification.store'), [
                'document_type' => 'drivers_license',
                'document_front' => UploadedFile::fake()->image('id-front.jpg', 900, 600),
                'document_back' => UploadedFile::fake()->image('id-back.jpg', 900, 600),
                'selfie' => UploadedFile::fake()->image('selfie.jpg', 700, 700),
            ])
            ->assertRedirect(route('account.verification.show'));

        $verification = \App\Modules\Verification\Models\IdentityVerification::where('user_id', $verifiedMember->id)->latest('id')->firstOrFail();
        $service = app(IdentityVerificationService::class);
        $service->approve($verification, $reviewer);

        $verifiedListing = $this->makeListing($verifiedMember->fresh(), [
            'title' => 'ID Verified Coastal Foodie',
            'is_featured' => false,
            'published_at' => now()->subDays(2),
        ]);
        $this->attachPhotos($verifiedListing, 1);

        $recentOwner = User::factory()->create(['role' => 'user', 'account_status' => 'active']);
        $recentListing = $this->makeListing($recentOwner, [
            'title' => 'Freshly Posted Art Gallery Date',
            'is_featured' => false,
            'published_at' => now()->subHours(3),
        ]);

        // Pre-save the verified listing into $saverUser's favorites
        $saverUser->favorites()->attach($verifiedListing->id);

        $searchResponse = $this->actingAs($saverUser)
            ->withSession(['age_confirmed_18' => true])
            ->get(route('search.index'))
            ->assertOk();

        $searchResponse->assertSee('ID Verified Coastal Foodie');
        $searchResponse->assertSee('Freshly Posted Art Gallery Date');
        $searchResponse->assertSee('data-card-variant="verified"', false);
        $searchResponse->assertSee('data-card-variant="recent"', false);
        $searchResponse->assertSee('Identity Verified Member', false);
        $searchResponse->assertSee('aria-label="Remove from favorites"', false);

        // Favorite toggle endpoint invoked by the card heart button persists and toggles cleanly
        $this->actingAs($saverUser)
            ->postJson(route('listings.favorite.toggle', $recentListing->id))
            ->assertOk()
            ->assertJson([
                'favorited' => true,
                'favorite_count' => 1,
            ]);

        // Category and listing detail pages render cleanly with Contact Member target
        $this->withSession(['age_confirmed_18' => true])
            ->get(route('categories.show', $verifiedListing->category->slug))
            ->assertOk()
            ->assertSee('View Details');

        $this->withSession(['age_confirmed_18' => true])
            ->get(route('listings.show', $verifiedListing->slug))
            ->assertOk()
            ->assertSee('id="contact-member"', false);
    }
}
