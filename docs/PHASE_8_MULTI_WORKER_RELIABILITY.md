# LocalDateHub — Phase 8 Multi-Worker Reliability

**Date:** 2026-11-14

---

## 1. Real Multi-Process Worker Contention (MySQL)

- **Tested:** 100 parallel PHP child processes (`4 × 25` concurrent) invoking `RecordPlatformTrafficJob::handle()` against live MySQL `localdatehub_phase8_test`.
- **Result:** `CONCURRENT_100_SAME_KEY_ROWS=1` (zero duplicate `platform_traffic_logs` rows). `18` processes exited non-zero under 25-way same-row `visitor_sessions` contention (correctly rethrowing non-unique exceptions for `$tries = 3`, `$backoff = [5, 30, 60]` queue retry).
- **Queue Replay (`queue:retry all`):** Verified idempotent in `Phase7ReliabilityValidationTest::test_failed_job_replay_command_is_idempotent_with_event_key`.
- **Live Horizon/Supervisor Worker Crash (`SIGKILL` / `SIGTERM` drain):** **BLOCKED** locally (`laravel/horizon` and `supervisorctl` absent).
