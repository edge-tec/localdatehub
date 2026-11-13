# LocalDateHub — Phase 7 Queue Recovery Report

**Date:** 2026-11-14

---

## 1. Verified Recovery Mechanisms

- **Bounded Retries & Backoff:** `RecordPlatformTrafficJob` enforces `$tries = 3`, `$maxExceptions = 3`, `$backoff = [5, 30, 60]`, `$timeout = 30`.
- **Timeout Alignment:** `config/queue.php` `redis.retry_after` (`120s`) > `supervisor-core` (`90s`) > `supervisor-comms` (`60s`) > `supervisor-analytics` (`30s`), and Supervisor `stopwaitsecs=3600` accommodates `--max-time=3600` graceful drain.
- **Permanent Failure Hook (`failed()`):** Logs structured context (`event_id`, `path`, `country_code`, `exception`, `error`) without `ip_address` or credentials, and increments `ldh:metrics:analytics_permanent_failures` (verified in `test_failed_job_handler_records_permanent_failure_without_logging_sensitive_fields`).
- **Idempotent Failed-Job Replay (`php artisan queue:retry all`):** Verified in `test_failed_job_replay_command_is_idempotent_with_event_key` — replaying a failed `RecordPlatformTrafficJob` preserves `1` unique row for `event_key`.
- **Queue Health Visibility:** `HealthCheckService::checkQueue()` marks `GET /health/detailed` HTTP `503` (`degraded`) when `failed_jobs_24h > 100` (verified in `test_health_readiness_marks_queue_degraded_when_failed_jobs_exceed_threshold`).

---

## 2. Unverified Runtime Scenarios

- Live `SIGKILL` / worker mid-flight crash recovery and multi-process Horizon `SIGTERM` drain remain **BLOCKED** locally until staging Redis + Horizon/Supervisor are provisioned.

