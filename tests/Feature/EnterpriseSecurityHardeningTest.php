<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackPlatformTraffic;
use App\Jobs\RecordPlatformTrafficJob;
use App\Models\User;
use App\Modules\Advertising\Services\AdUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EnterpriseSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_ad_html_sanitizer_strips_xss_payloads_and_preserves_safe_markup(): void
    {
        $payloads = [
            '<script>alert(1)</script><a href="https://example.com" target="_blank">Click</a>',
            '<img src="x" onerror="alert(document.cookie)">',
            '<svg onload="alert(1)"><circle r="10"/></svg>',
            '<iframe src="https://evil.example/phish"></iframe>',
            '<a href="javascript:alert(1)">Bad Link</a>',
            '<a href="java&#x09;script:alert(1)">Obfuscated</a>',
            '<img src="data:text/html,<script>alert(1)</script>">',
            '<div style="background:url(javascript:alert(1))">Styled</div>',
        ];

        foreach ($payloads as $payload) {
            $clean = (string) AdUrlService::sanitizeHtmlSnippet($payload);
            $this->assertStringNotContainsString('<script', strtolower($clean));
            $this->assertStringNotContainsString('<iframe', strtolower($clean));
            $this->assertStringNotContainsString('<svg', strtolower($clean));
            $this->assertStringNotContainsString('onerror', strtolower($clean));
            $this->assertStringNotContainsString('onload', strtolower($clean));
            $this->assertStringNotContainsString('javascript:', strtolower($clean));
            $this->assertStringNotContainsString('data:text/html', strtolower($clean));
            $this->assertStringNotContainsString('style=', strtolower($clean));
        }

        $safe = AdUrlService::sanitizeHtmlSnippet('<div class="ad-box"><a href="https://localdatehub.com/promo" target="_blank"><strong>Offer</strong></a></div>');
        $this->assertStringContainsString('href="https://localdatehub.com/promo"', (string) $safe);
        $this->assertStringContainsString('rel="noopener noreferrer"', (string) $safe);
        $this->assertStringContainsString('<strong>Offer</strong>', (string) $safe);
    }

    public function test_security_headers_and_csp_report_only_are_present(): void
    {
        $response = $this->get(route('pages.contact'));

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy', "frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self';");
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_sensitive_routes_send_private_no_store_and_noindex_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.wallet.index'));

        $response->assertStatus(200);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }

    public function test_treasury_emergency_endpoints_enforce_granular_treasury_permission(): void
    {
        $supportStaff = User::factory()->create(['role' => 'customer_support']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($supportStaff)
            ->postJson('/api/v1/admin/treasury/emergency-pause')
            ->assertStatus(403);

        $this->actingAs($supportStaff)
            ->postJson('/api/v1/admin/treasury/emergency-resume')
            ->assertStatus(403);

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/treasury/emergency-pause')
            ->assertStatus(200)
            ->assertJson(['outbound_paused' => true]);

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/treasury/emergency-resume')
            ->assertStatus(200)
            ->assertJson(['outbound_paused' => false]);
    }

    public function test_broad_api_wildcard_is_not_excluded_from_csrf_middleware(): void
    {
        $bootstrapContent = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertIsString($bootstrapContent);
        $this->assertMatchesRegularExpression('/validateCsrfTokens\(except:\s*\[[^\]]*\'api\/payments\/webhook\/\*\'/s', $bootstrapContent);
        $this->assertDoesNotMatchRegularExpression('/validateCsrfTokens\(except:\s*\[[^\]]*\'api\/\*\'/s', $bootstrapContent);
    }

    public function test_async_visitor_tracking_dispatches_queued_job_when_non_sync_queue_configured(): void
    {
        Queue::fake();
        Config::set('queue.default', 'redis');
        Config::set('analytics.async_enabled', true);
        Config::set('analytics.queue', 'analytics');

        $response = $this->get(route('pages.about'));
        $response->assertStatus(200);

        Queue::assertPushedOn('analytics', RecordPlatformTrafficJob::class);
    }

    public function test_bot_traffic_does_not_dispatch_analytics_job(): void
    {
        Queue::fake();
        Config::set('queue.default', 'redis');
        Config::set('analytics.async_enabled', true);

        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
            ->get(route('pages.about'))
            ->assertStatus(200);

        Queue::assertNotPushed(RecordPlatformTrafficJob::class);
    }

    public function test_async_traffic_job_deduplicates_same_path_and_keeps_distinct_paths(): void
    {
        $basePayload = [
            'ip_address' => '203.0.113.10',
            'visitor_hash' => hash('sha256', '203.0.113.10|test'),
            'country_code' => 'US',
            'country_name' => 'United States',
            'path' => '/about',
            'full_url' => 'http://localhost/about',
            'method' => 'GET',
            'user_agent' => 'Mozilla/5.0 TestBrowser',
            'device_type' => 'Desktop',
            'browser' => 'Chrome',
            'os' => 'macOS',
            'visitor_cookie_id' => 'v_test1234567890',
            'is_new_cookie' => false,
            'duration_ms' => 15,
        ];

        $tracker = app(\App\Modules\Analytics\Services\LiveVisitorTrackingService::class);
        (new RecordPlatformTrafficJob($basePayload))->handle($tracker);
        // Duplicate dispatch (e.g. retry or reload within 30 min) for same path
        (new RecordPlatformTrafficJob($basePayload))->handle($tracker);

        $this->assertSame(1, \App\Models\PlatformTrafficLog::where('visitor_hash', $basePayload['visitor_hash'])->where('path', '/about')->count());

        // Distinct path must NOT be suppressed
        $secondPayload = array_merge($basePayload, [
            'path' => '/safety',
            'full_url' => 'http://localhost/safety',
        ]);
        (new RecordPlatformTrafficJob($secondPayload))->handle($tracker);

        $this->assertSame(2, \App\Models\PlatformTrafficLog::where('visitor_hash', $basePayload['visitor_hash'])->count());
    }

    public function test_real_queue_worker_consumes_dispatched_analytics_job_without_fake(): void
    {
        Config::set('queue.default', 'database');
        Config::set('analytics.async_enabled', true);
        Config::set('analytics.queue', 'analytics');

        // Dispatch via real HTTP GET request (not Queue::fake)
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/126.0.0.0 Safari/537.36',
            'CF-IPCountry' => 'US',
        ])->get(route('pages.about'))->assertStatus(200);

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('platform_traffic_logs', 0);

        // Process the queued job using the real Laravel queue:work command
        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            'connection' => 'database',
            '--queue' => 'analytics',
            '--once' => true,
        ]);

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('platform_traffic_logs', 1);
    }

    public function test_adversarial_xss_and_namespace_confusion_payloads_are_neutralized(): void
    {
        $adversarial = [
            '<scr<script>ipt>alert(1)</script>',
            '<math><mtext><table><mglyph><style><!--</style><img title="--&gt;&lt;img src=x onerror=alert(1)&gt;">',
            '<a href="JaVaScRiPt:alert(1)">MixedCase</a>',
            '<a href="vbscript:msgbox(1)">VB</a>',
            '<object data="https://evil.example/a.swf"></object><embed src="https://evil.example/b.swf">',
            '<a href="https://example.com" onclick=alert(1) onmouseover="fetch(1)">SafeText</a>',
        ];

        foreach ($adversarial as $payload) {
            $clean = (string) AdUrlService::sanitizeHtmlSnippet($payload);
            $this->assertStringNotContainsString('<script', strtolower($clean));
            $this->assertStringNotContainsString('<object', strtolower($clean));
            $this->assertStringNotContainsString('<embed', strtolower($clean));
            $this->assertStringNotContainsString('<math', strtolower($clean));
            $this->assertStringNotContainsString('javascript:', strtolower($clean));
            $this->assertStringNotContainsString('vbscript:', strtolower($clean));
            $this->assertStringNotContainsString('onclick', strtolower($clean));
            $this->assertStringNotContainsString('onmouseover', strtolower($clean));
        }
    }

    public function test_record_platform_traffic_job_has_retry_timeout_and_safe_failed_handler(): void
    {
        $job = new RecordPlatformTrafficJob([
            'path' => '/about',
            'country_code' => 'US',
            'visitor_hash' => 'secret-hash',
            'ip_address' => '198.51.100.1',
        ]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(3, $job->maxExceptions);
        $this->assertSame(30, $job->timeout);
        $this->assertSame([5, 30, 60], $job->backoff);

        // Should execute failed() cleanly without throwing
        $job->failed(new \RuntimeException('Simulated transient DB failure'));
        $this->assertTrue(true);
    }

    public function test_analytics_queue_worker_isolation_does_not_consume_other_queues(): void
    {
        Config::set('queue.default', 'database');
        Config::set('analytics.async_enabled', true);
        Config::set('analytics.queue', 'analytics');

        // Dispatch one analytics job and one default-queue job
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/126.0.0.0 Safari/537.36',
            'CF-IPCountry' => 'US',
        ])->get(route('pages.about'))->assertStatus(200);

        \Illuminate\Support\Facades\DB::table('jobs')->insert([
            'queue' => 'critical',
            'payload' => json_encode(['displayName' => 'TestCriticalJob', 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'data' => []]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        // Run only analytics worker once: critical job must remain untouched
        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            'connection' => 'database',
            '--queue' => 'analytics',
            '--once' => true,
        ]);

        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('jobs')->where('queue', 'analytics')->count());
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('jobs')->where('queue', 'critical')->count());
    }

    public function test_analytics_database_idempotency_survives_cache_flush(): void
    {
        $eventKey = hash('sha256', 'idempotent-visitor|/about|bucket-1');
        $payload = [
            'event_id' => (string) \Illuminate\Support\Str::uuid(),
            'event_key' => $eventKey,
            'ip_address' => '203.0.113.20',
            'visitor_hash' => hash('sha256', 'idempotent-visitor'),
            'country_code' => 'US',
            'country_name' => 'United States',
            'path' => '/about',
            'full_url' => 'http://localhost/about',
            'method' => 'GET',
            'user_agent' => 'Mozilla/5.0 IdempotencyTest',
            'device_type' => 'Desktop',
            'browser' => 'Chrome',
            'os' => 'macOS',
            'visitor_cookie_id' => 'v_idem123456789',
            'is_new_cookie' => false,
            'duration_ms' => 12,
        ];

        $tracker = app(\App\Modules\Analytics\Services\LiveVisitorTrackingService::class);
        (new RecordPlatformTrafficJob($payload))->handle($tracker);

        // Wipe cache completely (simulating Redis restart/eviction); DB unique constraint must still block duplicate
        \Illuminate\Support\Facades\Cache::flush();
        (new RecordPlatformTrafficJob($payload))->handle($tracker);

        $this->assertSame(1, \App\Models\PlatformTrafficLog::where('event_key', $eventKey)->count());
    }

    public function test_analytics_job_degrades_gracefully_when_cache_throws_exception(): void
    {
        $eventKey = hash('sha256', 'cache-failure-visitor|/safety|bucket-1');
        $payload = [
            'event_id' => (string) \Illuminate\Support\Str::uuid(),
            'event_key' => $eventKey,
            'ip_address' => '203.0.113.21',
            'visitor_hash' => hash('sha256', 'cache-failure-visitor'),
            'country_code' => 'US',
            'country_name' => 'United States',
            'path' => '/safety',
            'full_url' => 'http://localhost/safety',
            'method' => 'GET',
            'user_agent' => 'Mozilla/5.0 CacheFailureTest',
            'device_type' => 'Desktop',
            'browser' => 'Chrome',
            'os' => 'macOS',
            'visitor_cookie_id' => 'v_cachefail123',
            'is_new_cookie' => false,
            'duration_ms' => 10,
        ];

        \Illuminate\Support\Facades\Cache::shouldReceive('add')->andThrow(new \RuntimeException('Redis connection refused'));
        \Illuminate\Support\Facades\Cache::shouldReceive('has')->andReturn(false);
        \Illuminate\Support\Facades\Cache::shouldReceive('put')->andThrow(new \RuntimeException('Redis connection refused'));

        $tracker = app(\App\Modules\Analytics\Services\LiveVisitorTrackingService::class);
        (new RecordPlatformTrafficJob($payload))->handle($tracker);

        $this->assertSame(1, \App\Models\PlatformTrafficLog::where('event_key', $eventKey)->count());
    }

    public function test_health_readiness_endpoint_enforces_secret_key_authorization(): void
    {
        Config::set('localdatehub.health_check.secret_key', 'test-health-secret-123');

        $this->getJson('/health/detailed')->assertStatus(403);
        $this->getJson('/health/detailed?key=wrong-key')->assertStatus(403);

        $ok = $this->withHeaders(['X-Health-Key' => 'test-health-secret-123'])->getJson('/health/detailed');
        $ok->assertStatus(200);
        $ok->assertJsonPath('data.checks.queue.status', 'healthy');
    }
}
