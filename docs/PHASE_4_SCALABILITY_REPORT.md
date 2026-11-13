# LocalDateHub — Phase 4 Scalability Report

## 1. Analytics & Queue Architecture
- **Async Dispatch:** `TrackPlatformTraffic` dispatches `RecordPlatformTrafficJob` to the `analytics` queue when `QUEUE_CONNECTION !== 'sync'`.
- **Reliability & Recovery:** `RecordPlatformTrafficJob` enforces `$tries = 3`, `$maxExceptions = 3`, `$backoff = [5, 30, 60]`, `$timeout = 30`, and a PII-safe `failed()` handler recording failures to application logs and `failed_jobs`.
- **Deduplication:** 30-minute `Cache::add` key (`ldh:traffic_dedup:{visitor_hash}:{md5(path)}`) prevents duplicate rows on retries/reloads while keeping distinct paths separate.

## 2. MySQL & Read-Replica Architecture (Design Only — External Nodes Not Activated)
- **Analytics Indexes:** `2026_11_14_000001_add_high_traffic_analytics_indexes.php` adds `ptl_hash_path_created_idx` and `ptl_created_bot_idx` on `platform_traffic_logs`.
- **Read/Write Split Strategy:**
  - **Primary Only (Strict Consistency):** Wallet ledger writes, deposits, sweeps, auth/sessions, checkout orders, admin treasury controls.
  - **Read Replicas Eligible:** Public homepage, state/city directory browsing, category listings, search queries, admin analytics aggregations (with `sticky => true` enabled).

## 3. Cloudflare WAF, CDN & Rate Limiting Plan
- **Cache Everything:** `/build/*`, `/images/*` (immutable versioned Vite assets).
- **Bypass Cache:** `/account*`, `/admin*`, `/api*`, `/checkout*`, `/contact*`, `/login`, `/register`, `/webhooks*`, or whenever `laravel_session` cookie is present.
- **Monitoring Targets:** Track CPU, RAM, PHP-FPM active workers, RPS, p95/p99 latency, MySQL active connections & slow queries, Redis memory/evictions, and queue depth/oldest job age.

