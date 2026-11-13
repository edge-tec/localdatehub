# LocalDateHub — Phase 6 Observability & Monitoring

**Scope:** Structured analytics metrics, PII-free logging, and `/health/detailed` readiness probe.

---

## 1. Analytics Observability Counters

`App\Jobs\RecordPlatformTrafficJob` maintains lightweight, PII-free counters (best-effort cache increments, never throwing on cache outage):
- `analytics_processed`: successfully inserted new `platform_traffic_logs` rows.
- `analytics_duplicates`: deduplicated events (via cache fast-path or DB unique-key race handler).
- `analytics_failures`: transient job execution failures caught before retry.
- `analytics_permanent_failures`: terminal failures recorded in `failed(\Throwable $exception)`.

No raw IP addresses, user emails, or authorization tokens are included in job failure logs.

---

## 2. Health & Readiness Endpoints

- **Public Liveness (`GET /health`)**: Lightweight liveness check for load balancers/uptime monitors.
- **Protected Readiness (`GET /health/detailed`)**:
  - Protected by `HealthCheckAuthMiddleware` (`health.auth` middleware).
  - Enforces `X-Health-Key` (or configured secret) using constant-time `hash_equals(...)` comparison to prevent timing side-channels.
  - Returns `checks.queue` from `HealthCheckService::checkQueue()` (`status`, `failed_jobs_24h`, `pending_db_jobs`) alongside DB and cache checks.

---

## 3. Recommended Production Alerts

| Signal | Source | Threshold | Action |
| :--- | :--- | :--- | :--- |
| Readiness Probe | `GET /health/detailed` | HTTP != `200` or `checks.queue.status == degraded` | Page on-call SRE; inspect Horizon/workers & `failed_jobs`. |
| Permanent Job Failures | `analytics_permanent_failures` / `failed_jobs` | `> 0` in 5m window | Inspect exception logs; fix root cause; replay via `queue:retry`. |
| Queue Backlog | `pending_db_jobs` / Horizon `analytics` queue | Sustained growth > 5m | Scale worker concurrency or inspect DB write latency. |

