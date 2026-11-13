<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
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
}
