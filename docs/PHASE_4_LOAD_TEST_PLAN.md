# LocalDateHub — Phase 4 Staged Load Test Plan

## 1. Tooling & Script
- Script: `tests/load/k6-million-traffic.js`
- Profiles supported via `STAGE`: `smoke` (10 VUs), `100`, `500`, `1000`, `5000`, `10000`, `full`.
- **Safety Rule:** Execute **ONLY** against an isolated staging environment (`BASE_URL=https://staging.localdatehub.com`). Never run against production or execute real payment/wallet mutations.

## 2. Progressive Execution Stages
| Stage | Command | Target VUs | Stop Criteria |
|---|---|---:|---|
| Smoke | `STAGE=smoke k6 run tests/load/k6-million-traffic.js` | 10 | Any 5xx or setup error |
| Stage 1 | `STAGE=100 k6 run tests/load/k6-million-traffic.js` | 100 | Error rate >= 1% or p95 > 500ms |
| Stage 2 | `STAGE=500 k6 run tests/load/k6-million-traffic.js` | 500 | MySQL connections > 80% or Redis mem > 80% |
| Stage 3 | `STAGE=1000 k6 run tests/load/k6-million-traffic.js` | 1,000 | CPU >= 90% or queue backlog growing continuously |
| Stage 4 | `STAGE=5000 k6 run tests/load/k6-million-traffic.js` | 5,000 | Requires Load Balancer + Redis + CDN |
| Stage 5 | `STAGE=10000 k6 run tests/load/k6-million-traffic.js` | 10,000 | Requires horizontally scaled app & DB replicas |

## 3. Metrics to Capture Per Stage
- RPS, avg, p50, p90, p95, p99 latency, HTTP 4xx/5xx error rate.
- PHP-FPM active/idle workers, MySQL `Threads_connected` & slow queries, Redis `used_memory` & `ops/sec`, queue depth & `failed_jobs`.
- **Execution Status:** **BLOCKED** locally (`k6` binary and staging server unavailable).

