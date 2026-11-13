# LocalDateHub — Phase 7 Concurrency Test Results

**Date:** 2026-11-14  
**Database Engine Used in Automated Tests:** SQLite `:memory:` (`phpunit.xml` default).  
**Important Disclosure:** Multi-process MySQL 8.x concurrency against a live `mysqld` instance is **NOT TESTED** / **BLOCKED** locally because `/tmp/mysql.sock` is not running.

---

## 1. Executed Concurrency & Idempotency Scenarios

1. **Scenario A — 75 Duplicate `event_key` Operations (`test_seventy_five_duplicate_event_key_operations_persist_exactly_one_row`):**
   - Executed `75` independent `RecordPlatformTrafficJob::handle()` calls with identical `event_key` while calling `Cache::flush()` on every iteration to bypass cache deduplication.
   - **Observed Result:** `1` row persisted in `platform_traffic_logs` for `event_key`; `0` unhandled exceptions.
   - **Status:** **VERIFIED** (SQLite `:memory:`); MySQL multi-worker race **PARTIALLY VERIFIED** (via `SQLSTATE[23000]`/`1062` handler test).

2. **Scenario B — Legitimate Distinct Events (`test_different_paths_and_visitors_are_preserved_without_accidental_deduplication`):**
   - Dispatched `5` events across distinct paths (`/`, `/categories`, `/locations`, `/search`) and distinct visitors (`v1`, `v2`, `v3`).
   - **Observed Result:** All `5` rows preserved in `platform_traffic_logs`.
   - **Status:** **VERIFIED**.

3. **Scenario C — Retry After Partial Failure (`test_retry_after_partial_failure_does_not_create_duplicate_rows`):**
   - Simulated `RuntimeException` inside `LiveVisitorTrackingService::recordPageVisitFromPayload`, verified `0` rows persisted on failed attempt, then retried twice.
   - **Observed Result:** Exactly `1` row persisted after retries.
   - **Status:** **VERIFIED**.

4. **Scenario D — Multi-Worker Queue Concurrency:**
   - **Status:** **BLOCKED** locally (`redis-server`, `laravel/horizon`, `supervisorctl` not installed).

