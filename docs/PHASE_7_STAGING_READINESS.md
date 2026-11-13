# LocalDateHub — Phase 7 Staging Readiness Checklist

**Date:** 2026-11-14  
**Production Deployment:** **DISABLED** (requires explicit authorization).

---

## 1. Code & Configuration Readiness (Complete)

- [x] `2026_11_14_000002_add_event_key_to_platform_traffic_logs.php` reversible unique `event_key` migration
- [x] `RecordPlatformTrafficJob` `ShouldBeUnique` + `DB::transaction(..., 3)` + `firstOrCreate` + `QueryException` race handler
- [x] `TrackPlatformTraffic` `dispatchSync` fallback on broker outage
- [x] `config/queue.php` `redis.retry_after = 120` (> max Horizon timeout `90s`)
- [x] `docs/supervisor/localdatehub-workers.conf` explicit `--timeout` flags aligned with Horizon
- [x] `HealthCheckAuthMiddleware` constant-time `hash_equals` + `HealthCheckService::checkQueue()`

---

## 2. Staging Provisioning Blockers (Required Before Production Sign-off)

1. Provision staging **MySQL 8.x** database and run `Phase7ReliabilityValidationTest` + multi-process concurrency test against real InnoDB.
2. Install & start **Redis** + `phpredis` (or `predis/predis`) and test `QUEUE_CONNECTION=redis` / `CACHE_STORE=redis` failover.
3. Install **`laravel/horizon`** + **Supervisor** (activating **only** `localdatehub-horizon.conf` OR `localdatehub-workers.conf`, never both).
4. Execute **`k6` staged load tests** (`STAGE=smoke`, `100`, `500`, `1000`, `5000`, `10000`) against staging and collect real RPS, p50/p95/p99, CPU/RAM, DB connections, and queue latency.

