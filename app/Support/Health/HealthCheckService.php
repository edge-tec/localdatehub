<?php

namespace App\Support\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthCheckService
{
    /**
     * Perform basic liveness probe.
     */
    public function checkLiveness(): array
    {
        return [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'app' => config('app.name'),
            'env' => config('app.env'),
        ];
    }

    /**
     * Perform comprehensive readiness and dependency health inspection.
     */
    public function checkReadiness(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'analytics' => $this->checkAnalytics(),
            'system' => $this->checkSystem(),
        ];

        $allHealthy = collect($checks)->every(fn ($check) => ($check['status'] ?? '') === 'healthy');

        return [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'app' => config('app.name'),
            'env' => config('app.env'),
            'checks' => $checks,
        ];
    }

    protected function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            $connection = DB::connection();
            $connection->getPdo();
            $durationMs = round((microtime(true) - $start) * 1000, 2);

            $result = [
                'status' => 'healthy',
                'latency_ms' => $durationMs,
                'connection' => DB::getDefaultConnection(),
                'driver' => $connection->getDriverName(),
            ];

            if ($connection->getDriverName() === 'mysql') {
                $threadsRow = $connection->selectOne("SHOW STATUS LIKE 'Threads_connected'");
                $maxRow = $connection->selectOne("SHOW VARIABLES LIKE 'max_connections'");
                $threads = isset($threadsRow->Value) ? (int) $threadsRow->Value : null;
                $maxConn = isset($maxRow->Value) ? (int) $maxRow->Value : null;
                $result['threads_connected'] = $threads;
                $result['max_connections'] = $maxConn;
                if ($threads !== null && $maxConn > 0) {
                    $result['utilization_pct'] = round(($threads / $maxConn) * 100, 2);
                }
            }

            return $result;
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'connection' => DB::getDefaultConnection(),
            ];
        }
    }

    protected function checkCache(): array
    {
        $start = microtime(true);
        try {
            $key = 'health_check_' . uniqid();
            Cache::put($key, 'ok', 5);
            $value = Cache::get($key);
            Cache::forget($key);

            $durationMs = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => $value === 'ok' ? 'healthy' : 'degraded',
                'latency_ms' => $durationMs,
                'store' => config('cache.default'),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'store' => config('cache.default'),
            ];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $disk = config('filesystems.default', 'local');
            $testFile = 'health_test_' . uniqid() . '.txt';
            Storage::disk($disk)->put($testFile, 'test');
            Storage::disk($disk)->delete($testFile);

            return [
                'status' => 'healthy',
                'disk' => $disk,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'disk' => config('filesystems.default', 'local'),
            ];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default', 'sync');
            $failedLast24h = \Illuminate\Support\Facades\Schema::hasTable('failed_jobs')
                ? DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count()
                : 0;
            $pendingDbJobs = ($connection === 'database' && \Illuminate\Support\Facades\Schema::hasTable('jobs'))
                ? DB::table('jobs')->count()
                : null;

            return [
                'status' => $failedLast24h > 100 ? 'degraded' : 'healthy',
                'connection' => $connection,
                'failed_jobs_24h' => $failedLast24h,
                'pending_db_jobs' => $pendingDbJobs,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'degraded',
                'connection' => config('queue.default', 'sync'),
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkAnalytics(): array
    {
        try {
            return [
                'status' => 'healthy',
                'processed_24h' => (int) Cache::get('ldh:metrics:analytics_processed', 0),
                'duplicates_24h' => (int) Cache::get('ldh:metrics:analytics_duplicates', 0),
                'retries_24h' => (int) Cache::get('ldh:metrics:analytics_retries', 0),
                'failures_24h' => (int) Cache::get('ldh:metrics:analytics_failures', 0),
                'permanent_failures_24h' => (int) Cache::get('ldh:metrics:analytics_permanent_failures', 0),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'degraded',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkSystem(): array
    {
        return [
            'status' => 'healthy',
            'php_version' => PHP_VERSION,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
    }
}
