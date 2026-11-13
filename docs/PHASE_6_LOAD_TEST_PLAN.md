# LocalDateHub — Phase 6 Load Test Plan

**Status:** **NOT TESTED** / **BLOCKED** locally (`k6`, `redis-server`, `laravel/horizon`, `supervisorctl`, and dedicated staging target are not present in the local macOS workspace).

---

## 1. Staged Load Profile Targets (Staging Only)

Never run full load profiles against production. Execute sequentially against an authorized staging environment with Redis + Horizon + Supervisor active:

1. `STAGE=smoke` — Sanity verification (1–5 VUs, 1m)
2. `STAGE=100` — Baseline concurrency (100 VUs)
3. `STAGE=500` — Moderate traffic burst (500 VUs)
4. `STAGE=1000` — High concurrency (1,000 VUs)
5. `STAGE=5000` — Stress test (5,000 VUs)
6. `STAGE=10000` — Peak capacity ceiling check (10,000 VUs)

---

## 2. Metrics to Capture Per Stage

- Requests per second (RPS)
- Latency percentiles (`p50`, `p95`, `p99`)
- HTTP error rate (`4xx`/`5xx`)
- Application & DB CPU/RAM utilization
- Active MySQL connections vs `max_connections`
- Queue processing latency (`analytics`, `core`, `communications`)
- `analytics_processed` vs `analytics_duplicates` vs `analytics_failures`
- `GET /health/detailed` queue status (`healthy` vs `degraded`)

---

## 3. Pass/Fail Acceptance Criteria (Staging)

- Error rate < 1% under target concurrency
- Zero duplicate `event_key` rows in `platform_traffic_logs`
- Zero unhandled exceptions during simulated Redis restart/flush
- Queue backlog drains to 0 within SLA after burst ends

