<?php

namespace App\Jobs;

use App\Models\PlatformTrafficLog;
use App\Modules\Analytics\Services\LiveVisitorTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecordPlatformTrafficJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 30, 60];

    public int $timeout = 30;

    public int $uniqueFor = 60;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public array $payload
    ) {
        $this->onQueue((string) config('analytics.queue', 'analytics'));
    }

    public function uniqueId(): string
    {
        return (string) ($this->payload['event_key'] ?? md5(json_encode($this->payload) ?: ''));
    }

    public function handle(LiveVisitorTrackingService $liveTracker): void
    {
        $startedAt = microtime(true);
        $eventId = (string) ($this->payload['event_id'] ?? '');
        $hash = (string) ($this->payload['visitor_hash'] ?? '');
        $path = substr((string) ($this->payload['path'] ?? '/'), 0, 255);
        $dedupTtl = max(60, (int) config('analytics.dedup_ttl_seconds', 1800));
        $bucket = (int) floor(now()->timestamp / $dedupTtl);
        $eventKey = (string) ($this->payload['event_key'] ?? ($hash !== '' ? hash('sha256', $hash . '|' . $path . '|' . $bucket) : ''));

        if ($this->attempts() > 1) {
            $this->recordMetric('analytics_retries');
        }

        try {
            $liveTracker->recordPageVisitFromPayload($this->payload);

            // Fast cache short-circuit when healthy; cache failure degrades gracefully to DB unique constraint
            $cacheDedup = false;
            if ($hash !== '') {
                try {
                    $dedupKey = 'ldh:traffic_dedup:' . $hash . ':' . md5($path);
                    $cacheDedup = !Cache::add($dedupKey, 1, $dedupTtl);
                } catch (\Throwable $cacheException) {
                    Log::warning('Analytics cache deduplication unavailable; relying on DB idempotency', [
                        'event_id' => $eventId,
                        'error' => $cacheException->getMessage(),
                    ]);
                }
            }

            if ($cacheDedup) {
                $this->recordMetric('analytics_duplicates');
                return;
            }

            $wasDuplicate = false;
            try {
                DB::transaction(function () use ($eventKey, $hash, $path, &$wasDuplicate) {
                    if ($eventKey !== '') {
                        $record = PlatformTrafficLog::firstOrCreate(
                            ['event_key' => $eventKey],
                            $this->buildLogAttributes($hash, $path)
                        );
                        $wasDuplicate = !$record->wasRecentlyCreated;
                    } else {
                        PlatformTrafficLog::create($this->buildLogAttributes($hash, $path));
                    }
                }, 3);
            } catch (QueryException $qe) {
                if ($this->isUniqueConstraintViolation($qe)) {
                    $wasDuplicate = true;
                } else {
                    throw $qe;
                }
            }

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $this->recordMetric($wasDuplicate ? 'analytics_duplicates' : 'analytics_processed');

            Log::info('Analytics event processed', [
                'event_id' => $eventId,
                'path' => $path,
                'country_code' => $this->payload['country_code'] ?? 'US',
                'deduplicated' => $wasDuplicate,
                'processing_ms' => $durationMs,
            ]);
        } catch (\Throwable $e) {
            $this->recordMetric('analytics_failures');
            Log::warning('Async traffic recording failed', [
                'event_id' => $eventId,
                'path' => $path,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle permanent job failure after retries are exhausted without logging PII.
     */
    public function failed(\Throwable $exception): void
    {
        $this->recordMetric('analytics_permanent_failures');
        Log::error('RecordPlatformTrafficJob permanently failed', [
            'event_id' => $this->payload['event_id'] ?? null,
            'path' => $this->payload['path'] ?? null,
            'country_code' => $this->payload['country_code'] ?? null,
            'exception' => get_class($exception),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildLogAttributes(string $hash, string $path): array
    {
        return [
            'ip_address' => $this->payload['ip_address'] ?? '127.0.0.1',
            'visitor_hash' => $hash,
            'country_code' => $this->payload['country_code'] ?? 'US',
            'country_name' => $this->payload['country_name'] ?? 'United States',
            'city_name' => null,
            'state_code' => null,
            'path' => $path,
            'method' => $this->payload['method'] ?? 'GET',
            'referrer' => $this->payload['referrer'] ?? null,
            'user_agent' => $this->payload['user_agent'] ?? 'Mozilla/5.0',
            'device_type' => $this->payload['device_type'] ?? 'Desktop',
            'browser' => $this->payload['browser'] ?? 'Chrome',
            'os' => $this->payload['os'] ?? 'Windows',
            'user_id' => $this->payload['user_id'] ?? null,
            'session_id' => $this->payload['session_id'] ?? null,
            'is_bot' => false,
            'response_time_ms' => (int) ($this->payload['duration_ms'] ?? 0),
            'created_at' => now(),
        ];
    }

    protected function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($driverCode, [1062, 19], true);
    }

    protected function recordMetric(string $metric): void
    {
        try {
            $key = 'ldh:metrics:' . $metric;
            Cache::add($key, 0, now()->addDay());
            Cache::increment($key);
        } catch (\Throwable) {
            // Metrics must never interrupt analytics persistence
        }
    }
}
