# LocalDateHub — Phase 9 Observability

**Date:** 2026-11-14  
**Status:** **VERIFIED**.

---

## 1. `/health` vs `/health/detailed` Enhancements

- **Public `GET /health`:** Minimal liveness payload (`status`, `timestamp`, `app`, `env`). Zero internal metrics or PII exposed.
- **Protected `GET /health/detailed` (`health.auth` `X-Health-Key` via `hash_equals`):**
  - `checks.database`: `status`, `latency_ms`, `connection`, `driver`, plus MySQL `threads_connected`, `max_connections`, and `utilization_pct` when running on `mysql`.
  - `checks.analytics`: `processed_24h`, `duplicates_24h`, `retries_24h` (added in Phase 9), `failures_24h`, `permanent_failures_24h`.
  - `checks.queue`: `failed_jobs_24h`, `pending_db_jobs`, returns HTTP `503` (`degraded`) when `failed_jobs_24h > 100`.
- **Redis Memory / Horizon Worker Utilization:** **BLOCKED** locally (no live Redis/Horizon runtime).
