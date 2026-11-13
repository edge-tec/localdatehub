# LocalDateHub — Phase 9 Queue Reliability

**Date:** 2026-11-14  
**Status:** Config invariants, `queue:retry all`, and multi-process MySQL job execution **VERIFIED**; Horizon / Supervisor `SIGTERM`/`SIGKILL` live daemon tests **BLOCKED**.

---

## 1. Horizon vs Supervisor Selection

- Both configurations exist in the repository (`config/horizon.php` + `docs/supervisor/localdatehub-horizon.conf` vs `docs/supervisor/localdatehub-workers.conf`).
- **Recommendation:** Use **Supervisor-managed Horizon (`localdatehub-horizon.conf`)** once `laravel/horizon` and Redis are installed in staging for auto-balancing across `core`, `analytics`, and `comms`, or standalone `localdatehub-workers.conf` if Horizon is not installed — **never both simultaneously**.

---

## 2. Verified vs Blocked

- **VERIFIED:** `retry_after (120s) > max timeout (90s)`, disjoint queues, `queue:retry all` idempotency, 100-process MySQL job execution with `0` failures and `1` unique row.
- **BLOCKED:** Live `SIGTERM` graceful drain, `SIGKILL` crash recovery, and Redis queue backlog replay (`redis-server`, `laravel/horizon`, `supervisorctl` absent locally).
