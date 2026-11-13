# LocalDateHub — Phase 5 Disaster Recovery & Rollback Plan

## 1. Pre-Change Backup Verification
- Before any staging/production change: verify MySQL dump (`mysqldump --single-transaction --routines --triggers`), verify disk space (`df -h`), and record current Git commit SHA (`git rev-parse HEAD`).

## 2. Failure & Recovery Procedures
- **Queue Worker Crash / Restart:** Supervisor (`autorestart=true`, `stopwaitsecs=3600`) restarts workers gracefully without killing in-flight jobs.
- **Failed Jobs Recovery:** Inspect `php artisan queue:failed` and replay safely via `php artisan queue:retry <id|all>` (`RecordPlatformTrafficJob` uses 30-min `Cache::add` deduplication; financial writes remain synchronous/transactional).
- **Application Rollback:** `php artisan down && git checkout <previous-sha> && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan queue:restart && php artisan up`.
- **Database Index Migration Rollback:** `php artisan migrate:rollback --step=1` safely drops `ptl_hash_path_created_idx` and `ptl_created_bot_idx` without touching table data.

