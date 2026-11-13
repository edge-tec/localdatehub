# LocalDateHub — Phase 7 MySQL 8.x Compatibility Audit

**Date:** 2026-11-14  
**Workspace:** `/Volumes/EDGE/Localdatehub`  
**Local MySQL Runtime Status:** `mysql Ver 26.7.0` / `mysqld` binary present at `/opt/homebrew/bin/mysql`, but local MySQL server is **NOT running** (`Can't connect to local MySQL server through socket '/tmp/mysql.sock'`). Live MySQL 8.x server execution is therefore **BLOCKED** locally; schema, SQLSTATE handling, and migration reversibility were audited by code inspection and SQLite `:memory:` execution.

---

## 1. Migration Audit: `2026_11_14_000002_add_event_key_to_platform_traffic_logs.php`

- **Column Type & Nullability:** `$table->string('event_key', 64)->nullable()->unique('ptl_event_key_unique')->after('visitor_hash')`.
  - **MySQL 8.x InnoDB Compatibility:** `VARCHAR(64)` with `utf8mb4` uses at most `64 * 4 = 256` bytes, well within InnoDB's `3072`-byte index key prefix limit.
  - **Multiple `NULL` Legacy Rows:** In MySQL 8.x (and SQLite), `UNIQUE` indexes permit multiple `NULL` values, so pre-existing rows with `event_key IS NULL` will not conflict during or after migration.
- **Index Naming & Reversibility:**
  - Explicit index name `ptl_event_key_unique` avoids auto-generated name length issues and enables deterministic rollback (`$table->dropUnique('ptl_event_key_unique')` followed by `$table->dropColumn('event_key')`).
  - Reversibility (`down()` -> `up()`) verified in `Phase7ReliabilityValidationTest::test_migration_allows_multiple_null_event_keys_and_is_reversible`.

---

## 2. Concurrency, Deadlock & Exception Handling Audit

- **Transaction Deadlock Retries:** `RecordPlatformTrafficJob` wraps `PlatformTrafficLog::firstOrCreate(['event_key' => $eventKey], ...)` in `DB::transaction(..., 3)` (Phase 7 enhancement), instructing Laravel to automatically retry MySQL InnoDB deadlocks (`SQLSTATE[40001]` / error `1213`) up to 3 times at the transaction layer.
- **Duplicate Key Race Handling:** `RecordPlatformTrafficJob::isUniqueConstraintViolation(QueryException $e)` inspects:
  - SQLSTATE `'23000'` (MySQL integrity constraint violation) and `'23505'` (PostgreSQL unique violation).
  - Driver error codes `1062` (MySQL `ER_DUP_ENTRY`) and `19` (SQLite `SQLITE_CONSTRAINT`).
  - Verified with simulated MySQL `SQLSTATE[23000]` / `1062` in `test_mysql_duplicate_key_sqlstate_23000_is_classified_as_unique_violation`.

---

## 3. Connection Exhaustion & Limits Guidance

- Local MySQL server is offline (`BLOCKED`); live `max_connections` stress testing was not executed.
- Recommended staging/production baseline: align PHP-FPM `pm.max_children` + Horizon `maxProcesses` (`6 + 8 + 4 = 18` workers) well below MySQL `max_connections` (e.g., `>= 150`), and keep `DB_PERSISTENT=false` unless using ProxySQL.

