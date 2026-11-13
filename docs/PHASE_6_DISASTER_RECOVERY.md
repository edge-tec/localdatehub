# LocalDateHub — Phase 6 Disaster Recovery Runbook

**Environment Rule:** Local development only; production untouched. All staging/production recovery actions require explicit operator authorization and pre-change backups.

---

## 1. Failure Scenarios & Automated/Manual Recovery

| Scenario | Automated Protection (Code) | Operator Recovery Procedure |
| :--- | :--- | :--- |
| **Redis Outage / Cache Eviction** | `RecordPlatformTrafficJob` catches `Cache::add` exceptions and falls back to MySQL `ptl_event_key_unique` + `firstOrCreate`. `TrackPlatformTraffic` falls back to `dispatchSync` if queue dispatch throws. | 1. Restore Redis service.<br>2. Verify `/health/detailed` returns `200`.<br>3. No duplicate analytics rows are created upon recovery. |
| **Worker Crash Mid-Job** | `DB::transaction` wraps `firstOrCreate`; partial writes roll back. `$tries = 3`, `$backoff = [5, 30, 60]`, `$timeout = 30`. | 1. Supervisor/Horizon restarts worker.<br>2. Re-queued job hits `ptl_event_key_unique` if already committed, or cleanly inserts if rolled back. |
| **Failed Job Accumulation** | `failed()` logs structured context and increments `analytics_permanent_failures`. `checkQueue()` flags `degraded`. | 1. Run `php artisan queue:failed`.<br>2. Fix underlying DB/config issue.<br>3. Safely run `php artisan queue:retry all` (idempotent). |
| **Migration Rollback** | `2026_11_14_000002_add_event_key_to_platform_traffic_logs.php` implements reversible `down()` dropping `ptl_event_key_unique` and `event_key`. | `php artisan migrate:rollback --step=1` (after DB backup). |

