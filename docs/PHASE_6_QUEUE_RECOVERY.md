# LocalDateHub — Phase 6 Queue Recovery & Failure Handling

**Scope:** `App\Http\Middleware\TrackPlatformTraffic`, `App\Jobs\RecordPlatformTrafficJob`, `App\Support\Health\HealthCheckService`

---

## 1. Dispatch Resilience

In `App\Http\Middleware\TrackPlatformTraffic`:
- Asynchronous `RecordPlatformTrafficJob::dispatch(...)` is wrapped in `try/catch (\Throwable $e)`.
- If the configured queue driver (e.g., Redis) fails at dispatch time, the middleware logs a structured warning and falls back to `RecordPlatformTrafficJob::dispatchSync(...)` so analytics events are not silently dropped during transient broker outages.

---

## 2. Worker Retry, Backoff & Timeout Policy

`App\Jobs\RecordPlatformTrafficJob` defines explicit bounded retry and execution limits:
- `$tries = 3`
- `$maxExceptions = 3`
- `$backoff = [5, 30, 60]` (seconds)
- `$timeout = 30` (seconds)

If all attempts exhaust, `failed(\Throwable $exception)` executes, logging structured failure context (without PII) and incrementing the `analytics_permanent_failures` observability counter.

---

## 3. Queue Health Visibility & Replay Procedure

`App\Support\Health\HealthCheckService::checkQueue()` inspects:
- `failed_jobs_24h`: count of failed jobs in `failed_jobs` within the last 24 hours.
- `pending_db_jobs`: count of pending jobs in `jobs` (when database queue tables exist).
- Marks queue check `degraded` if thresholds are exceeded, surfaced via `GET /health/detailed` (protected by `X-Health-Key` with constant-time `hash_equals`).

### Operator Replay Commands (Staging / Production)
```bash
php artisan queue:failed
php artisan queue:retry <id>
# Or retry all failed jobs safely (idempotent via event_key):
php artisan queue:retry all
```

Because `RecordPlatformTrafficJob` enforces DB-level `ptl_event_key_unique` + `firstOrCreate`, replaying failed jobs will not create duplicate `platform_traffic_logs` records.

---

## 4. Verification Status

- Code-level retry/backoff/fallback/transaction handling: **VERIFIED**
- Multi-worker Supervisor/Horizon crash & Redis failover runtime test: **BLOCKED** locally (`redis-server`, `laravel/horizon`, `supervisorctl` not installed in local environment).

