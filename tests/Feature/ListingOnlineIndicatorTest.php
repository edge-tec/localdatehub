<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListingOnlineIndicatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function makeListing(User $owner): Listing
    {
        $category = Category::where('slug', 'women-seeking-men')->first() ?? Category::first();
        $city = City::first();
        $state = $city?->state ?? State::first();

        return Listing::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'title' => 'Weekend Coffee Meetup ' . Str::random(5),
            'slug' => 'weekend-coffee-' . Str::random(8),
            'description' => 'Looking for genuine local connection over coffee and easy conversation.',
            'poster_age' => 28,
            'poster_gender' => 'Female',
            'intent' => 'dating',
            'status' => Listing::STATUS_ACTIVE,
            'has_photos' => false,
            'is_featured' => false,
            'published_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function test_active_now_and_green_dot_only_render_when_advertiser_is_online(): void
    {
        $neverActiveUser = User::factory()->create(['last_active_at' => null]);
        $neverActiveListing = $this->makeListing($neverActiveUser);

        $this->withSession(['age_confirmed_18' => true])
            ->get(route('listings.show', $neverActiveListing->slug))
            ->assertOk()
            ->assertDontSee('Active Now')
            ->assertDontSee('title="Active Now"', false);

        $staleUser = User::factory()->create(['last_active_at' => now()->subMinutes(12)]);
        $staleListing = $this->makeListing($staleUser);

        $this->withSession(['age_confirmed_18' => true])
            ->get(route('listings.show', $staleListing->slug))
            ->assertOk()
            ->assertDontSee('Active Now')
            ->assertDontSee('title="Active Now"', false);

        $onlineUser = User::factory()->create(['last_active_at' => now()->subMinutes(2)]);
        $onlineListing = $this->makeListing($onlineUser);

        $this->withSession(['age_confirmed_18' => true])
            ->get(route('listings.show', $onlineListing->slug))
            ->assertOk()
            ->assertSee('Active Now')
            ->assertSee('title="Active Now"', false);
    }

    public function test_heartbeat_updates_last_active_and_logout_marks_user_offline(): void
    {
        $user = User::factory()->create(['last_active_at' => null]);
        $this->assertFalse($user->isOnline());

        $this->actingAs($user)
            ->postJson(route('telemetry.heartbeat'), ['state' => 'active'])
            ->assertOk();

        $user->refresh();
        $this->assertTrue($user->isOnline());

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $user->refresh();
        $this->assertFalse($user->isOnline());
    }

    public function test_messenger_only_shows_online_indicator_when_peer_is_online(): void
    {
        $viewer = User::factory()->create(['last_active_at' => now()]);
        $offlinePeer = User::factory()->create(['last_active_at' => null]);
        $listing = $this->makeListing($offlinePeer);

        ContactMessage::create([
            'listing_id' => $listing->id,
            'sender_id' => $viewer->id,
            'recipient_id' => $offlinePeer->id,
            'sender_name' => $viewer->name,
            'sender_email_encrypted' => encrypt($viewer->email),
            'message_encrypted' => encrypt('Hello there'),
            'is_read' => false,
        ]);

        $this->actingAs($viewer)
            ->withSession(['age_confirmed_18' => true])
            ->get(route('account.messages.index'))
            ->assertOk()
            ->assertDontSee('Select Member')
            ->assertDontSee('Attach to Classified Listing')
            ->assertDontSee('Active now')
            ->assertDontSee('title="Active now"', false);

        $offlinePeer->forceFill(['last_active_at' => now()->subMinute()])->saveQuietly();

        $this->actingAs($viewer)
            ->withSession(['age_confirmed_18' => true])
            ->get(route('account.messages.index'))
            ->assertOk()
            ->assertSee('Active now')
            ->assertSee('title="Active now"', false);
    }
}
