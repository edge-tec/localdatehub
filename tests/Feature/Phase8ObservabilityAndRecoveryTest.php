<?php

namespace Tests\Feature;

use App\Jobs\RecordPlatformTrafficJob;
use App\Modules\Analytics\Models\VisitorSession;
use App\Modules\Analytics\Services\LiveVisitorTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase8ObservabilityAndRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_detailed_exposes_analytics_observability_counters_under_auth(): void
    {
        Config::set('localdatehub.health_check.secret_key', 'phase8-health-secret');

        $job = new RecordPlatformTrafficJob([
            'event_id' => (string) Str::uuid(),
            'event_key' => hash('sha256', 'obs-test|/about-us|100'),
            'ip_address' => '198.51.100.12',
            'visitor_hash' => hash('sha256', 'obs-visitor'),
            'path' => '/about-us',
            'full_url' => 'http://localhost/about-us',
            'method' => 'GET',
            'user_agent' => 'Mozilla/5.0',
            'country_code' => 'US',
            'country_name' => 'United States',
            'duration_ms' => 9,
        ]);

        $service = app(LiveVisitorTrackingService::class);
        $job->handle($service);
        $job->handle($service); // duplicate

        $res = $this->withHeaders(['X-Health-Key' => 'phase8-health-secret'])->getJson('/health/detailed');
        $res->assertStatus(200);
        $res->assertJsonPath('data.checks.analytics.status', 'healthy');
        $res->assertJsonStructure([
            'data' => [
                'checks' => [
                    'database' => ['status', 'latency_ms', 'connection', 'driver'],
                    'analytics' => ['status', 'processed_24h', 'duplicates_24h', 'retries_24h', 'failures_24h', 'permanent_failures_24h'],
                ],
            ],
        ]);
        $this->assertGreaterThanOrEqual(1, (int) $res->json('data.checks.analytics.processed_24h'));
        $this->assertGreaterThanOrEqual(1, (int) $res->json('data.checks.analytics.duplicates_24h'));

        // Public liveness must never expose internal counters or checks
        $liveness = $this->getJson('/health');
        $liveness->assertStatus(200);
        $liveness->assertJsonMissingPath('data.checks.analytics');
    }

    public function test_live_visitor_tracking_handles_existing_visitor_session_cleanly(): void
    {
        $service = app(LiveVisitorTrackingService::class);
        $request = Request::create('http://localhost/categories', 'GET', [], [], [], [
            'REMOTE_ADDR' => '198.51.100.55',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        ]);

        $first = $service->recordPageVisit($request, '/categories', 15);
        $this->assertInstanceOf(VisitorSession::class, $first['session']);

        $cookieReq = Request::create('http://localhost/locations', 'GET', [], [
            LiveVisitorTrackingService::VISITOR_COOKIE => $first['visitor_cookie_value'],
        ], [], [
            'REMOTE_ADDR' => '198.51.100.55',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        ]);

        $second = $service->recordPageVisit($cookieReq, '/locations', 12);
        $this->assertSame($first['session']->id, $second['session']->id);
        $this->assertSame(1, VisitorSession::count());
    }
}
