# LocalDateHub — Phase 8 MySQL Concurrency Results

**Date:** 2026-11-14  
**Database Tested:** Live local `mysqld` (`26.7.0`) on `127.0.0.1:3306`, isolated schema `localdatehub_phase8_test` (`max_connections = 151`).

---

## 1. Migration & Schema Verification

- `php artisan migrate:fresh --force` on `localdatehub_phase8_test`: **Exit `0`**
- Reversible `down()` -> `up()` of `2026_11_14_000002_add_event_key_to_platform_traffic_logs.php`: **PASS**
- Multiple legacy `NULL` `event_key` rows inserted: **`2` rows** (`NULL_EVENT_KEYS_COUNT=2`), confirming `ptl_event_key_unique` allows multiple `NULL` values.

---

## 2. 100-Process Concurrent Idempotency & Distinct Event Results

| Test | Execution Method | Result |
| :--- | :--- | :--- |
| **100 Concurrent Same `event_key`** | 4 batches × 25 parallel `proc_open` PHP child processes executing `RecordPlatformTrafficJob::handle()` against `localdatehub_phase8_test` | `CONCURRENT_100_SAME_KEY_ROWS=1` (exactly **1** row persisted), `CONCURRENT_100_DURATION_MS=2372` (`2.37s`), `CONCURRENT_100_FAILURES=18` (transient same-row `visitor_sessions` contention rethrown for queue retry; zero duplicate `platform_traffic_logs` rows) |
| **100 Distinct `event_key`s** | 100 unique visitor/path combinations | `TOTAL_ROWS_AFTER_100_DISTINCT=103` (`2` NULL + `1` concurrent + `100` distinct = **100%** preserved), `DISTINCT_100_DURATION_MS=699` |

### Concurrency Fix Discovered & Applied in Phase 8
- During initial 25-way parallel testing, `LiveVisitorTrackingService::recordPageVisit()` exhibited a check-then-insert race on `visitor_sessions.session_token`.
- **Fix Applied (`app/Modules/Analytics/Services/LiveVisitorTrackingService.php`):** Wrapped `VisitorSession::create()` in `try/catch (QueryException)` handling `23000`/`23505`/`1062`/`19` by fetching the winning concurrent `VisitorSession` and skipping duplicate `VisitorActivity('entered')` creation.
