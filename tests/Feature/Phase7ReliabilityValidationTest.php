<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackPlatformTraffic;
use App\Jobs\RecordPlatformTrafficJob;
use App\Models\PlatformTrafficLog;
use App\Modules\Analytics\Services\LiveVisitorTrackingService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase7ReliabilityValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_allows_multiple_null_event_keys_and_is_reversible(): void
    {
        $this->assertTrue(Schema::hasColumn('platform_traffic_logs', 'event_key'));

        PlatformTrafficLog::create([
            'ip_address' => '127.0.0.1',
            'visitor_hash' => hash('sha256', 'legacy-1'),
            'event_key' => null,
            'path' => '/legacy-1',
            'method' => 'GET',
            'created_at' => now(),
        ]);
        PlatformTrafficLog::create([
            'ip_address' => '127.0.0.2',
            'visitor_hash' => hash('sha256', 'legacy-2'),
            'event_key' => null,
            'path' => '/legacy-2',
            'method' => 'GET',
            'created_at' => now(),
        ]);

        $this->assertSame(2, PlatformTrafficLog::whereNull('event_key')->count());

        $migration = require database_path('migrations/2026_11_14_000002_add_event_key_to_platform_traffic_logs.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('platform_traffic_logs', 'event_key'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn('platform_traffic_logs', 'event_key'));
    }

    public function test_seventy_five_duplicate_event_key_operations_persist_exactly_one_row(): void
    {
        $eventKey = hash('sha256', 'burst-visitor|/categories|1000');
        $visitorHash = hash('sha256', 'burst-visitor');
        $service = app(LiveVisitorTrackingService::class);

        for ($i = 0; $i < 75; $i++) {
            Cache::flush(); // Bypass cache fast-path on every iteration to stress DB idempotency
            $job = new RecordPlatformTrafficJob([
                'event_id' => (string) Str::uuid(),
                'event_key' => $eventKey,
                'ip_address' => '198.51.100.10',
                'visitor_hash' => $visitorHash,
                'path' => '/categories',
                'full_url' => 'http://localhost/categories',
                'method' => 'GET',
                'user_agent' => 'Mozilla/5.0',
                'country_code' => 'US',
                'country_name' => 'United States',
                'duration_ms' => 12,
            ]);
            $job->handle($service);
        }

        $this->assertSame(1, PlatformTrafficLog::where('event_key', $eventKey)->count());
    }

    public function test_different_paths_and_visitors_are_preserved_without_accidental_deduplication(): void
    {
        $service = app(LiveVisitorTrackingService::class);
        $events = [
            ['visitor' => 'v1', 'path' => '/'],
            ['visitor' => 'v1', 'path' => '/categories'],
            ['visitor' => 'v1', 'path' => '/locations'],
            ['visitor' => 'v2', 'path' => '/'],
            ['visitor' => 'v3', 'path' => '/search'],
        ];

        foreach ($events as $item) {
            $hash = hash('sha256', $item['visitor']);
            $eventKey = hash('sha256', $hash . '|' . $item['path'] . '|2000');
            (new RecordPlatformTrafficJob([
                'event_id' => (string) Str::uuid(),
                'event_key' => $eventKey,
                'ip_address' => '198.51.100.20',
                'visitor_hash' => $hash,
                'path' => $item['path'],
                'full_url' => 'http://localhost' . $item['path'],
                'method' => 'GET',
                'user_agent' => 'Mozilla/5.0',
                'country_code' => 'US',
                'country_name' => 'United States',
                'duration_ms' => 10,
            ]))->handle($service);
        }

        $this->assertSame(5, PlatformTrafficLog::count());
    }

    public function test_retry_after_partial_failure_does_not_create_duplicate_rows(): void
    {
        $eventKey = hash('sha256', 'retry-visitor|/pricing|3000');
        $payload = [
            'event_id' => (string) Str::uuid(),
            'event_key' => $eventKey,
            'ip_address' => '198.51.100.30',
            'visitor_hash' => hash('sha256', 'retry-visitor'),
            'path' => '/pricing',
            'full_url' => 'http://localhost/pricing',
            'method' => 'GET',
            'user_agent' => 'Mozilla/5.0',
            'country_code' => 'US',
            'country_name' => 'United States',
            'duration_ms' => 15,
        ];

        $failingLiveTracker = $this->createMock(LiveVisitorTrackingService::class);
        $failingLiveTracker->expects($this->once())
            ->method('recordPageVisitFromPayload')
            ->willThrowException(new \RuntimeException('Simulated transient worker crash'));

        try {
            (new RecordPlatformTrafficJob($payload))->handle($failingLiveTracker);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated transient worker crash', $e->getMessage());
        }

        $this->assertSame(0, PlatformTrafficLog::where('event_key', $eventKey)->count());

        // Retry succeeds cleanly
        $service = app(LiveVisitorTrackingService::class);
        (new RecordPlatformTrafficJob($payload))->handle($service);
        (new RecordPlatformTrafficJob($payload))->handle($service);

        $this->assertSame(1, PlatformTrafficLog::where('event_key', $eventKey)->count());
    }

    public function test_mysql_duplicate_key_sqlstate_23000_is_classified_as_unique_violation(): void
    {
        $job = new class([]) extends RecordPlatformTrafficJob {
            public function checkUnique(QueryException $e): bool
            {
                return $this->isUniqueConstraintViolation($e);
            }
        };

        $pdoEx = new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry');
        $pdoEx->errorInfo = ['23000', 1062, 'Duplicate entry'];
        $qe = new QueryException('mysql', 'insert into platform_traffic_logs ...', [], $pdoEx);

        $this->assertTrue($job->checkUnique($qe));
    }

    public function test_queue_dispatch_failure_falls_back_to_sync_and_logs_warning(): void
    {
        Config::set('queue.default', 'redis');
        Config::set('analytics.async_enabled', true);

        Log::spy();

        $middleware = new TrackPlatformTraffic();
        $request = Request::create('/about-us', 'GET', [], [], [], [
            'REMOTE_ADDR' => '198.51.100.40',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        ]);

        $response = $middleware->handle($request, fn () => new Response('ok', 200));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, PlatformTrafficLog::where('path', '/about-us')->count());

        Log::shouldHaveReceived('warning')->atLeast()->once();
    }

    public function test_bot_requests_are_filtered_from_analytics_tracking(): void
    {
        Config::set('queue.default', 'sync');

        $middleware = new TrackPlatformTraffic();
        $request = Request::create('/categories', 'GET', [], [], [], [
            'REMOTE_ADDR' => '66.249.66.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ]);

        $response = $middleware->handle($request, fn () => new Response('ok', 200));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, PlatformTrafficLog::count());
    }

    public function test_failed_job_handler_records_permanent_failure_without_logging_sensitive_fields(): void
    {
        Log::spy();

        $job = new RecordPlatformTrafficJob([
            'event_id' => 'evt-perm-fail-1',
            'path' => '/locations',
            'country_code' => 'US',
            'ip_address' => '203.0.113.99',
        ]);

        $job->failed(new \RuntimeException('Deadlock found when trying to get lock'));

        $this->assertGreaterThanOrEqual(1, (int) Cache::get('ldh:metrics:analytics_permanent_failures'));
        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return $message === 'RecordPlatformTrafficJob permanently failed'
                && ($context['event_id'] ?? null) === 'evt-perm-fail-1'
                && !array_key_exists('ip_address', $context)
                && !array_key_exists('password', $context);
        })->once();
    }

    public function test_horizon_queue_isolation_and_retry_after_timeout_invariants(): void
    {
        $horizon = config('horizon');
        $retryAfter = (int) config('queue.connections.redis.retry_after');

        $this->assertContains('admin.rbac', $horizon['middleware']);
        $this->assertContains('auth', $horizon['middleware']);

        $seenQueues = [];
        foreach ($horizon['defaults'] as $supervisorName => $settings) {
            $this->assertGreaterThan(
                (int) $settings['timeout'],
                $retryAfter,
                "Redis retry_after ({$retryAfter}) must exceed {$supervisorName} timeout ({$settings['timeout']})."
            );

            foreach ($settings['queue'] as $queueName) {
                $this->assertNotContains(
                    $queueName,
                    $seenQueues,
                    "Queue '{$queueName}' is duplicated across Horizon supervisors."
                );
                $seenQueues[] = $queueName;
            }
        }
    }

    public function test_supervisor_worker_and_horizon_configs_are_valid_and_aligned(): void
    {
        $workersConf = file_get_contents(base_path('docs/supervisor/localdatehub-workers.conf'));
        $horizonConf = file_get_contents(base_path('docs/supervisor/localdatehub-horizon.conf'));

        $this->assertIsString($workersConf);
        $this->assertIsString($horizonConf);

        $this->assertStringContainsString('--queue=critical,sweeping,verification,monitoring,default', $workersConf);
        $this->assertStringContainsString('--queue=analytics', $workersConf);
        $this->assertStringContainsString('--queue=notifications,emails,advertising,maintenance', $workersConf);
        $this->assertStringContainsString('--timeout=90', $workersConf);
        $this->assertStringContainsString('--timeout=30', $workersConf);
        $this->assertStringContainsString('--timeout=60', $workersConf);
        $this->assertStringContainsString('autostart=true', $workersConf);
        $this->assertStringContainsString('autorestart=true', $workersConf);
        $this->assertStringContainsString('stopwaitsecs=3600', $workersConf);

        $this->assertStringContainsString('artisan horizon', $horizonConf);
        $this->assertStringContainsString('NEVER both at the same time', $horizonConf);
    }

    public function test_health_readiness_marks_queue_degraded_when_failed_jobs_exceed_threshold(): void
    {
        Config::set('localdatehub.health_check.secret_key', 'phase7-secret-key');

        $rows = [];
        for ($i = 0; $i < 105; $i++) {
            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'connection' => 'database',
                'queue' => 'analytics',
                'payload' => '{}',
                'exception' => 'RuntimeException: test',
                'failed_at' => now(),
            ];
        }
        DB::table('failed_jobs')->insert($rows);

        $res = $this->withHeaders(['X-Health-Key' => 'phase7-secret-key'])->getJson('/health/detailed');
        $res->assertStatus(503);
        $res->assertJsonPath('data.checks.queue.status', 'degraded');
        $this->assertGreaterThan(100, (int) $res->json('data.checks.queue.failed_jobs_24h'));
    }

    public function test_failed_job_replay_command_is_idempotent_with_event_key(): void
    {
        $eventKey = hash('sha256', 'replay-visitor|/search|4000');
        $payload = [
            'event_id' => (string) Str::uuid(),
            'event_key' => $eventKey,
            'ip_address' => '198.51.100.50',
            'visitor_hash' => hash('sha256', 'replay-visitor'),
            'path' => '/search',
            'full_url' => 'http://localhost/search',
            'method' => 'GET',
            'user_agent' => 'Mozilla/5.0',
            'country_code' => 'US',
            'country_name' => 'United States',
            'duration_ms' => 11,
        ];

        $job = new RecordPlatformTrafficJob($payload);
        $service = app(LiveVisitorTrackingService::class);
        $job->handle($service);

        // Simulate re-recording into failed_jobs and replaying
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'sync',
            'queue' => 'analytics',
            'payload' => json_encode([
                'uuid' => (string) Str::uuid(),
                'displayName' => RecordPlatformTrafficJob::class,
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'maxTries' => 3,
                'data' => [
                    'commandName' => RecordPlatformTrafficJob::class,
                    'command' => serialize($job),
                ],
            ]),
            'exception' => 'RuntimeException: simulated transient failure',
            'failed_at' => now(),
        ]);

        Config::set('queue.default', 'sync');
        Artisan::call('queue:retry', ['id' => ['all']]);

        $this->assertSame(1, PlatformTrafficLog::where('event_key', $eventKey)->count());
    }
}
