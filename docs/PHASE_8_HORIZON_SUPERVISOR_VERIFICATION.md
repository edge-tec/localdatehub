# LocalDateHub — Phase 8 Horizon & Supervisor Verification

**Date:** 2026-11-14  
**Status:** Config & invariant validation **VERIFIED**; live daemon runtime **BLOCKED** (`laravel/horizon` and `supervisorctl` not installed locally).

---

## 1. Queue Isolation, Timeout & Mutual Exclusion Matrix

- **Horizon (`config/horizon.php`):**
  - `supervisor-core`: `['critical', 'sweeping', 'verification', 'monitoring', 'default']`, `timeout = 90s`, `maxProcesses = 6` (prod)
  - `supervisor-analytics`: `['analytics']`, `timeout = 30s`, `maxProcesses = 8` (prod)
  - `supervisor-comms`: `['notifications', 'emails', 'advertising', 'maintenance']`, `timeout = 60s`, `maxProcesses = 4` (prod)
  - Dashboard middleware: `['web', 'auth', 'admin.rbac']`.
- **Supervisor (`docs/supervisor/localdatehub-workers.conf` & `localdatehub-horizon.conf`):**
  - Aligned `--timeout=90`, `--timeout=30`, `--timeout=60`; `autostart=true`, `autorestart=true`, `stopwaitsecs=3600`.
  - Mutual exclusion enforced by operator policy: deploy **either** `localdatehub-horizon.conf` **or** `localdatehub-workers.conf`, never both.
- **Invariant Verified:** `config('queue.connections.redis.retry_after')` (`120s`) > max worker timeout (`90s`).
