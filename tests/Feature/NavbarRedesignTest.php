<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_navbar_renders_brand_links_dropdowns_and_auth_ctas(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('LocalDateHub');
        $response->assertSee('Connect • Meet • Enjoy');
        $response->assertSee('Home');
        $response->assertSee('Browse Ads');
        $response->assertSee('States');
        $response->assertSee('Cities');
        $response->assertSee('About');
        $response->assertSee('Contact');
        $response->assertSee('Post Ad');
        $response->assertSee('Sign In');
        $response->assertSee('Create Account');
        $response->assertSee('Filter states alphabetically...');
        $response->assertSee('Search popular cities...');
        $response->assertSee('mobileMenuOpen');
        $response->assertSee('Close menu');
        $response->assertDontSee('Admin Dashboard');
        $response->assertDontSee('Live Visitors Monitor');
        $response->assertDontSee('My Dashboard');
    }

    public function test_authenticated_regular_user_sees_account_dropdown_messages_and_notifications_without_admin_links(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Member',
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Jane Member');
        $response->assertSee('aria-label="Messages"', false);
        $response->assertSee('aria-label="Notifications"', false);
        $response->assertSee('My Dashboard');
        $response->assertSee('My Profile');
        $response->assertSee('My Advertisements');
        $response->assertSee('Saved Ads');
        $response->assertSee('Messages');
        $response->assertSee('Account Settings');
        $response->assertSee('Sign Out');
        $response->assertSee(route('notifications.mark-seen'), false);
        $response->assertDontSee('Admin Dashboard');
        $response->assertDontSee('Live Visitors Monitor');
    }

    public function test_authenticated_admin_sees_admin_dashboard_and_live_visitors_links(): void
    {
        $admin = User::factory()->create([
            'name' => 'Root Admin',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Root Admin');
        $response->assertSee('Admin Dashboard');
        $response->assertSee('Live Visitors Monitor');
        $response->assertSee(route('admin.dashboard'), false);
        $response->assertSee(route('admin.live-visitors'), false);
    }

    public function test_about_and_contact_pages_render_successfully_with_redesigned_navbar(): void
    {
        $this->get('/about')->assertStatus(200)->assertSee('LocalDateHub');
        $this->get('/contact')->assertStatus(200)->assertSee('LocalDateHub');
    }
}
