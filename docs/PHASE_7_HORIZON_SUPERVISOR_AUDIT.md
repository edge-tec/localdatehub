# LocalDateHub — Phase 7 Horizon & Supervisor Audit

**Date:** 2026-11-14  
**Status:** Static & automated configuration validation **VERIFIED** (`test_horizon_queue_isolation_and_retry_after_timeout_invariants`, `test_supervisor_worker_and_horizon_configs_are_valid_and_aligned`). Live Horizon/Supervisor process runtime **BLOCKED** locally (`laravel/horizon` and `supervisorctl` not installed).

---

## 1. `config/horizon.php` Audit

| Supervisor | Queues | Balance | `maxProcesses` (Prod) | `timeout` | `retry_after` (`config/queue.php`) | Isolation |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `supervisor-core` | `critical`, `sweeping`, `verification`, `monitoring`, `default` | `auto` | `6` | `90s` | `120s` (Fixed in Phase 7) | Disjoint |
| `supervisor-analytics` | `analytics` | `auto` | `8` | `30s` | `120s` | Disjoint |
| `supervisor-comms` | `notifications`, `emails`, `advertising`, `maintenance` | `auto` | `4` | `60s` | `120s` | Disjoint |

- **Dashboard Security:** `'middleware' => ['web', 'auth', 'admin.rbac']`.
- **Critical Phase 7 Fix:** `REDIS_QUEUE_RETRY_AFTER` default raised from `90` to `120` in `config/queue.php` so `retry_after > timeout` holds for every supervisor.

---

## 2. Supervisor Configurations Audit

- **`docs/supervisor/localdatehub-workers.conf`:**
  - `autostart=true`, `autorestart=true`, `stopasgroup=true`, `killasgroup=true`, `stopwaitsecs=3600`, log rotation (`stdout_logfile_maxbytes=20MB`, `stdout_logfile_backups=5`).
  - **Phase 7 Alignment Fix:** Added explicit `--timeout=90` (`localdatehub-core`), `--timeout=30` (`localdatehub-analytics`), and `--timeout=60` (`localdatehub-comms`) to match `config/horizon.php`.
- **`docs/supervisor/localdatehub-horizon.conf`:**
  - Explicitly documents mutual exclusion: **use EITHER `localdatehub-horizon.conf` OR `localdatehub-workers.conf` — NEVER both simultaneously**, preventing duplicate queue consumers.

