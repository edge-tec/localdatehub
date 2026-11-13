# LocalDateHub — Phase 7 Analytics Data Integrity

**Date:** 2026-11-14

---

## 1. End-to-End Integrity Verification

- **Middleware Filtering (`TrackPlatformTraffic`):**
  - Skips non-`GET`, AJAX/JSON/Livewire, prefetch/prerender, admin/moderator users, `/admin`, `/health`, `/horizon`, static assets, and crawlers/bots (`test_bot_requests_are_filtered_from_analytics_tracking`).
- **Deterministic Event Key & UUID:**
  - `event_id`: `Str::uuid()` per request for log correlation.
  - `event_key`: `hash('sha256', $visitorHash . '|' . $normalizedPath . '|' . $bucket)` using 30-minute windows (`config('analytics.dedup_ttl_seconds', 1800)`).
  - Different paths (`/`, `/categories`, `/locations`) and different visitors produce distinct `event_key` hashes and are never cross-deduplicated (`test_different_paths_and_visitors_are_preserved_without_accidental_deduplication`).
- **Atomic Metric Counter Fix (Phase 7):**
  - Updated `RecordPlatformTrafficJob::recordMetric()` to use atomic `Cache::add($key, 0, now()->addDay()); Cache::increment($key);` instead of non-atomic `has`/`put`.
- **PII & Secret Redaction:**
  - `failed()` and `handle()` logs exclude `ip_address`, `password`, and auth tokens.

