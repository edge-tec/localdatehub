# LocalDateHub — Phase 5 Load Benchmark Results

**Overall Load Testing Status:** `NOT TESTED` / `BLOCKED`

- **Blocker:** `k6` is not installed locally and no isolated staging server URL or Redis/MySQL staging infrastructure is available.
- **Safety Rule Respected:** Full load tests were **never** executed against production.
- **No Fabricated Numbers:** All metrics (`RPS`, `p50`, `p95`, `p99`, `error_rate`, `CPU`, `RAM`, `DB connections`, `queue latency`) remain `NOT TESTED` until executed on an authorized staging environment using `tests/load/k6-million-traffic.js`.

| Stage | Target VUs | Status | Measured RPS / p95 / p99 |
|---|---:|---|---|
| `STAGE=smoke` | 10 | `NOT TESTED` (`BLOCKED`) | Pending staging `k6` run |
| `STAGE=100` | 100 | `NOT TESTED` (`BLOCKED`) | Pending staging `k6` run |
| `STAGE=500` | 500 | `NOT TESTED` (`BLOCKED`) | Pending staging `k6` run |
| `STAGE=1000` | 1,000 | `NOT TESTED` (`BLOCKED`) | Pending staging `k6` run |
| `STAGE=5000` | 5,000 | `NOT TESTED` (`BLOCKED`) | Pending staging `k6` run |
| `STAGE=10000` | 10,000 | `NOT TESTED` (`BLOCKED`) | Pending staging `k6` run |

