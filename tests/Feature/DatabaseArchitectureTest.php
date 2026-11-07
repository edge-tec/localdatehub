<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\Permission;
use App\Modules\Admin\Models\Role;
use App\Modules\Categories\Models\Category;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Billing\Models\PricingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_us_states_and_cities_seeded_correctly(): void
    {
        $this->assertDatabaseHas('states', ['code' => 'TX', 'name' => 'Texas']);
        $this->assertDatabaseHas('states', ['code' => 'CA', 'name' => 'California']);
        $this->assertDatabaseHas('states', ['code' => 'NY', 'name' => 'New York']);

        $texas = State::where('code', 'TX')->first();
        $austin = City::where('slug', 'austin')->first();

        $this->assertNotNull($texas);
        $this->assertNotNull($austin);
        $this->assertEquals($texas->id, $austin->state_id);
        $this->assertTrue($austin->is_popular);
        $this->assertGreaterThan(0, $austin->zipCodes()->count());
    }

    public function test_categories_and_dynamic_attributes_relationship(): void
    {
        $m4w = Category::where('code', 'M4W')->first();

        $this->assertNotNull($m4w);
        $this->assertEquals('Men Seeking Women', $m4w->name);
        $this->assertTrue($m4w->is_active);

        $attributes = $m4w->attributes;
        $this->assertGreaterThanOrEqual(1, $attributes->count());

        $intentAttr = $attributes->firstWhere('code', 'intent');
        $this->assertNotNull($intentAttr);
        $this->assertGreaterThan(0, $intentAttr->options()->count());
    }

    public function test_user_profile_and_age_assurance_relationships(): void
    {
        $admin = User::where('email', 'admin@localdatehub.com')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_age_verified);
        $this->assertEquals('admin', $admin->role);

        $demoUser = User::where('username', 'alex_austin')->first();
        $this->assertNotNull($demoUser);
        $this->assertNotNull($demoUser->profile);
        $this->assertEquals('Austin', $demoUser->profile->city);
        $this->assertEquals(29, $demoUser->profile->age);
    }

    public function test_listing_creation_with_location_and_encrypted_contacts(): void
    {
        $user = User::where('username', 'alex_austin')->first();
        $listing = Listing::where('user_id', $user->id)->first();

        $this->assertNotNull($listing);
        $this->assertEquals('active', $listing->status);
        $this->assertNotNull($listing->location);
        $this->assertNotNull($listing->contact);

        // Verify location coordinates
        $this->assertNotNull($listing->location->latitude);
        $this->assertNotNull($listing->location->fuzzed_latitude);

        // Verify encrypted contact phone
        $listing->contact->preferred_phone_encrypted = '+15125550199';
        $listing->contact->save();

        // Reload fresh from DB
        $freshContact = $listing->contact()->first();
        $this->assertEquals('+15125550199', $freshContact->preferred_phone_encrypted);

        // Verify raw database column does not store plain text phone number
        $rawRow = DB::table('listing_contacts')->where('id', $freshContact->id)->first();
        $this->assertStringNotContainsString('+15125550199', $rawRow->preferred_phone_encrypted);
    }

    public function test_contact_message_encryption(): void
    {
        $sender = User::where('username', 'alex_austin')->first();
        $recipient = User::where('username', 'sarah_atx')->first();
        $listing = Listing::first();

        $message = ContactMessage::create([
            'listing_id' => $listing->id,
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'sender_name' => 'Alex Miller',
            'sender_email_encrypted' => 'alex.private@example.com',
            'sender_phone_encrypted' => '+15125559876',
            'subject' => 'Coffee on South Congress?',
            'message_encrypted' => 'Hi Sarah, would love to grab a coffee this weekend!',
        ]);

        $this->assertNotNull($message->id);
        $this->assertEquals('Hi Sarah, would love to grab a coffee this weekend!', $message->message_encrypted);
        $this->assertEquals('alex.private@example.com', $message->sender_email_encrypted);

        // Verify raw SQL stores ciphertext
        $rawMessage = DB::table('contact_messages')->where('id', $message->id)->first();
        $this->assertStringNotContainsString('grab a coffee this weekend', $rawMessage->message_encrypted);
        $this->assertStringNotContainsString('alex.private@example.com', $rawMessage->sender_email_encrypted);
    }

    public function test_rbac_roles_and_permissions(): void
    {
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $moderatorRole = Role::where('slug', 'listing_moderator')->first();

        $this->assertNotNull($superAdminRole);
        $this->assertNotNull($moderatorRole);

        $this->assertGreaterThan(5, $superAdminRole->permissions()->count());
        $this->assertTrue($superAdminRole->permissions()->where('slug', 'settings.manage')->exists());

        // Listing moderator has listings.approve but not settings.manage
        $this->assertTrue($moderatorRole->permissions()->where('slug', 'listings.approve')->exists());
        $this->assertFalse($moderatorRole->permissions()->where('slug', 'settings.manage')->exists());
    }

    public function test_pricing_plans_seeded(): void
    {
        $this->assertDatabaseHas('pricing_plans', ['slug' => 'free-listing']);
        $this->assertDatabaseHas('pricing_plans', ['slug' => 'city-featured']);
        $this->assertDatabaseHas('pricing_plans', ['slug' => 'verified-plus-monthly']);

        $featuredPlan = PricingPlan::where('slug', 'city-featured')->first();
        $this->assertEquals(499, $featuredPlan->price_cents);
        $this->assertEquals('USD', $featuredPlan->currency);
    }
}
