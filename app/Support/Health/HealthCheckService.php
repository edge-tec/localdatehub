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
            DB::connection()->getPdo();
            $durationMs = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'latency_ms' => $durationMs,
                'connection' => DB::getDefaultConnection(),
            ];
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
