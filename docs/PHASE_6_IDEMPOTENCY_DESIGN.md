# LocalDateHub — Phase 6 Idempotency Design

**Scope:** Analytics ingestion (`TrackPlatformTraffic` -> `RecordPlatformTrafficJob` -> `platform_traffic_logs`)

---

## 1. Multi-Layer Idempotency Architecture

Analytics event ingestion now enforces idempotency at three independent layers so that neither cache eviction, queue redelivery, nor concurrent workers can create duplicate traffic records for the same visitor/path/30-minute bucket:

1. **Layer 1 — Queue Uniqueness (`ShouldBeUnique`)**:
   - `App\Jobs\RecordPlatformTrafficJob` implements `Illuminate\Contracts\Queue\ShouldBeUnique`.
   - `$uniqueFor = 60` seconds.
   - `uniqueId()` returns the deterministic `event_key` (falling back to `event_id` if absent).
2. **Layer 2 — Fast-Path Cache Deduplication**:
   - `TrackPlatformTraffic` generates:
     - `event_id`: UUID per request.
     - `event_key`: `hash('sha256', $hash . '|' . $normalizedPath . '|' . $bucket)` where `$bucket` is a 30-minute time window.
   - `RecordPlatformTrafficJob::handle()` attempts `Cache::add("traffic_dedup:{$eventKey}", true, now()->addMinutes(30))` inside `try/catch (\Throwable)`.
   - If `Cache::add` throws (e.g., Redis unavailable), the job logs a warning and continues to Layer 3 instead of failing.
3. **Layer 3 — Database Unique Constraint & Atomic Transaction**:
   - Migration `2026_11_14_000002_add_event_key_to_platform_traffic_logs.php` adds nullable `event_key` (`string(64)`) with unique index `ptl_event_key_unique` on `platform_traffic_logs`.
   - `RecordPlatformTrafficJob` executes `PlatformTrafficLog::firstOrCreate(['event_key' => $eventKey], [...])` inside `DB::transaction(...)`.
   - Concurrent `QueryException` duplicate-key races (`23000`, `23505`, `1062`, `19`) are caught cleanly, incremented as `analytics_duplicates`, and treated as idempotent no-ops.

---

## 2. Verification Evidence

- `test_analytics_database_idempotency_survives_cache_flush` (in `tests/Feature/EnterpriseSecurityHardeningTest.php`):
  - Dispatches `RecordPlatformTrafficJob` with the same `event_key`, flushes cache via `Cache::flush()`, and dispatches again.
  - Asserts only `1` row exists in `platform_traffic_logs` for that `event_key`.
- `test_analytics_job_degrades_gracefully_when_cache_throws_exception`:
  - Mocks `Cache::shouldReceive('add')->andThrow(new \RuntimeException(...))`.
  - Asserts the job still persists the `PlatformTrafficLog` row via DB transaction without failing.

