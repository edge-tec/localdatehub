<?php

namespace Tests\Feature;

use App\Livewire\Admin\LiveVisitorMonitor;
use App\Models\User;
use App\Modules\Analytics\Models\VisitorActivity;
use App\Modules\Analytics\Models\VisitorPageview;
use App\Modules\Analytics\Models\VisitorSession;
use App\Modules\Analytics\Services\LiveVisitorTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LiveVisitorTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_navigation_updates_existing_session_without_duplicates(): void
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
            'CF-IPCountry' => 'CA',
            'CF-IPCity' => 'Toronto',
            'CF-Region' => 'Ontario',
            'REMOTE_ADDR' => '198.51.100.42',
        ];

        $first = $this->withHeaders($headers)->get('/?utm_source=google&utm_campaign=spring');
        $first->assertStatus(200);

        $this->assertSame(1, VisitorSession::count());
        $session = VisitorSession::first();
        $this->assertSame('CA', $session->country_code);
        $this->assertSame('Toronto', $session->city_name);
        $this->assertSame('Mobile', $session->device_type);
        $this->assertSame('Apple', $session->device_brand);
        $this->assertSame('iPhone', $session->device_model);
        $this->assertSame('iOS', $session->os_name);
        $this->assertSame('Safari', $session->browser_name);
        $this->assertSame('Campaign / Google', $session->traffic_source);
        $this->assertSame('online', $session->status);
        $this->assertSame(1, $session->pageviews_count);

        // Second page visit in same session should NOT create a duplicate visitor_session
        $second = $this->withHeaders($headers)
            ->withCookie(LiveVisitorTrackingService::VISITOR_COOKIE, $session->visitor_id)
            ->get('/about');
        $second->assertStatus(200);

        $this->assertSame(1, VisitorSession::count());
        $session->refresh();
        $this->assertSame(2, $session->pageviews_count);
        $this->assertSame(2, VisitorPageview::where('visitor_session_id', $session->id)->count());
        $this->assertTrue(VisitorActivity::where('visitor_session_id', $session->id)->where('event_type', 'navigated')->exists());
    }

    public function test_heartbeat_updates_screen_resolution_idle_and_departure_state(): void
    {
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0.0.0 Safari/537.36',
            'REMOTE_ADDR' => '203.0.113.10',
        ])->get('/');

        $session = VisitorSession::firstOrFail();

        $this->withCookie(LiveVisitorTrackingService::VISITOR_COOKIE, $session->visitor_id)
            ->post(route('telemetry.heartbeat'), [
                'state' => 'idle',
                'screen_resolution' => '1920x1080',
                'page_title' => 'LocalDateHub Home',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['tracked' => true, 'status' => 'idle']);

        $session->refresh();
        $this->assertSame('1920x1080', $session->screen_resolution);
        $this->assertSame('LocalDateHub Home', $session->current_page_title);
        $this->assertSame('idle', $session->status);

        $this->withCookie(LiveVisitorTrackingService::VISITOR_COOKIE, $session->visitor_id)
            ->post(route('telemetry.heartbeat'), ['state' => 'left'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['tracked' => true, 'status' => 'offline']);

        $session->refresh();
        $this->assertSame('offline', $session->status);
        $this->assertNotNull($session->ended_at);
    }

    public function test_inactive_sessions_expire_automatically(): void
    {
        $session = VisitorSession::create([
            'visitor_id' => 'v_stale_001',
            'session_token' => 's_stale_001',
            'ip_address' => '203.0.113.99',
            'country_code' => 'US',
            'country_name' => 'United States',
            'device_type' => 'Desktop',
            'os_name' => 'Windows',
            'browser_name' => 'Chrome',
            'status' => 'online',
            'first_seen_at' => now()->subMinutes(15),
            'last_activity_at' => now()->subMinutes(10),
        ]);

        /** @var LiveVisitorTrackingService $tracker */
        $tracker = app(LiveVisitorTrackingService::class);
        $this->assertSame(0, $tracker->getOnlineCount(300));

        $session->refresh();
        $this->assertSame('offline', $session->status);
        $this->assertTrue(VisitorActivity::where('visitor_session_id', $session->id)->where('event_type', 'expired')->exists());
    }

    public function test_only_authorized_admins_can_access_live_visitor_monitor(): void
    {
        $regularUser = User::factory()->create(['role' => 'user', 'account_status' => 'active']);
        $this->actingAs($regularUser)->get(route('admin.live-visitors'))->assertStatus(403);

        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        $this->actingAs($admin)->get(route('admin.live-visitors'))->assertOk();

        $session = VisitorSession::create([
            'visitor_id' => 'v_live_admin_check',
            'session_token' => 's_live_admin_check',
            'ip_address' => '198.51.100.77',
            'country_code' => 'US',
            'country_name' => 'United States',
            'city_name' => 'Austin',
            'device_type' => 'Desktop',
            'os_name' => 'macOS',
            'browser_name' => 'Chrome',
            'current_page_url' => 'http://localhost/',
            'current_page_title' => 'Home Page',
            'status' => 'online',
            'first_seen_at' => now()->subMinute(),
            'last_activity_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(LiveVisitorMonitor::class)
            ->assertSee('Real-Time Live Visitor Monitoring')
            ->assertSee('198.51.100.77')
            ->assertSee('Austin')
            ->call('inspectVisitor', $session->id)
            ->assertSee('Visitor Session Profile');
    }
}
