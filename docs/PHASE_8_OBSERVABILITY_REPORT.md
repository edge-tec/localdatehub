# LocalDateHub — Phase 8 Observability Report

**Date:** 2026-11-14

---

## 1. Health & Telemetry Endpoints

- **Public Liveness (`GET /health`):** Returns only `status`, `timestamp`, `app`, `env` — zero internal metrics, secrets, or PII (`test_health_detailed_exposes_analytics_observability_counters_under_auth`).
- **Protected Readiness (`GET /health/detailed`, middleware `health.auth` with `hash_equals`):**
  - `checks.database` (latency & status)
  - `checks.cache` (latency, store & status)
  - `checks.storage` (disk write/delete probe)
  - `checks.queue` (`failed_jobs_24h`, `pending_db_jobs`, marks `503 degraded` if `failed_jobs_24h > 100`)
  - **Phase 8 Addition (`checks.analytics`):** `processed_24h`, `duplicates_24h`, `failures_24h`, `permanent_failures_24h` from atomic `ldh:metrics:*` counters.
  - `checks.system` (`php_version`, `memory_usage_mb`, `memory_peak_mb`)

---

## 2. Alerting Thresholds

| Metric | Warning | Critical |
| :--- | :--- | :--- |
| `/health/detailed` HTTP status | `!= 200` once | Sustained `503` > 2m |
| `failed_jobs_24h` | `> 20` | `> 100` (auto-`degraded`) |
| `analytics_permanent_failures` | `> 0` | `>= 5` in 15m |
| MySQL `Threads_connected / max_connections` | `> 70%` | `> 85%` |
