# LocalDateHub — Phase 9B: Isolated Staging Infrastructure Preparation & Validation

**Date:** 2026-11-14  
**Mode:** Read-Only Audit Completed — **Awaiting Explicit User Approval** before installing packages or provisioning staging services.  
**Production & HostVra Status:** Completely untouched.

---

## 1. Read-Only Infrastructure & Compatibility Audit

| Component | Current State | Required / Compatible Version | Status |
| :--- | :--- | :--- | :--- |
| **OS / Arch** | `Darwin 27.2.0` (`arm64`, 10 cores, 16 GiB RAM) | macOS Homebrew (`/opt/homebrew/bin/brew`) | **VERIFIED** |
| **PHP Runtime** | `PHP 8.5.6 (cli)` + `Zend OPcache v8.5.6` | `composer.json`: `"php": "^8.3"` | **VERIFIED** |
| **Laravel Framework** | `13.40.0` | `composer.json`: `"laravel/framework": "^13.17"` | **VERIFIED** |
| **MySQL Server** | `mysqld 26.7.0` (`127.0.0.1:3306`, `max_connections=151`) | Dedicated isolated schema (e.g., `localdatehub_staging`) | **VERIFIED** (engine available; staging DB not yet provisioned) |
| **Redis Server** | Not installed | `redis` (`>= 7.x` via `brew install redis`), isolated port `127.0.0.1:6380` + ACL + RDB + AOF | **BLOCKED** (requires approval to install) |
| **PHP Redis Client** | Neither `phpredis` nor `predis/predis` installed | `pecl install redis` (`phpredis >= 6.0` for PHP 8.5) or `predis/predis` (`^2.x` fallback) | **BLOCKED** (requires approval to install) |
| **Queue Orchestrator** | `laravel/horizon` & `supervisor` not installed | `laravel/horizon` (`^5.x` compatible with Laravel 13) + `supervisor` (`brew install supervisor`) — mutually exclusive modes | **BLOCKED** (requires approval to install) |
| **Load Runner** | `k6` not installed | `k6` (`brew install k6`) | **BLOCKED** (requires approval to install) |
| **Automated Test Suite** | `php artisan test --compact` | `489` passed, `0` failed, `0` skipped, `3,437` assertions (`53,998 ms`) | **VERIFIED** |

---

## 2. Proposed Isolated Staging Provisioning Plan (Pending Approval)

Once explicitly approved by the user, the following zero-cost local isolated staging steps will be executed without touching production or HostVra:

1. **Install Staging Binaries & Packages:**
   - `brew install redis supervisor k6`
   - `pecl install redis` (for `phpredis` on PHP 8.5.6; fallback `composer require predis/predis` if PECL compilation requires extra build headers)
   - `composer require laravel/horizon` (or keep Supervisor-only worker mode if preferred)
2. **Provision Isolated Services & Credentials:**
   - Dedicated MySQL schema: `localdatehub_staging` (isolated user/credentials, separate from production & unrelated local DBs).
   - Dedicated Redis instance on `127.0.0.1:6380` with custom `redis-staging.conf` (`requirepass` / `ACL` user, `appendonly yes` AOF + RDB snapshots, `maxmemory 512mb`, `maxmemory-policy noeviction` for queue safety).
   - Dedicated `.env.staging` (never committed, separate `APP_KEY`, DB, and Redis credentials).
3. **Execute Phase 9B Validation Drills:**
   - Worker reliability (`SIGTERM` graceful drain, `SIGKILL` crash recovery, retry/backoff, duplicate protection, backlog recovery).
   - Redis disaster recovery (`BGSAVE` RDB backup/restore, AOF replay, restart with ACL auth).
   - Progressive `k6` load testing (`smoke`, `100`, `1000`, `5000`, `10000` VUs) against isolated local staging HTTP server, stopping immediately if safety thresholds breach.
   - Final full test verification (`php artisan test --compact`).

---

## 3. Current Phase 9B Verification Matrix

| Scope | Status | Notes |
| :--- | :--- | :--- |
| 1. Initial Read-Only Audit & Dependency Report | **VERIFIED** | PHP `8.5.6`, Laravel `13.40.0`, `brew`/`pecl`/`mysqld` verified; missing packages identified. |
| 2. Isolated Staging Infrastructure Provisioning | **BLOCKED** | Awaiting explicit user approval before installing `redis`, `phpredis`, `horizon`/`supervisor`, `k6`. |
| 3. Worker Reliability (`SIGTERM`/`SIGKILL` Live Drill) | **BLOCKED** | Depends on Step 2 provisioning. |
| 4. Redis Disaster Recovery (RDB/AOF/ACL) | **BLOCKED** | Depends on Step 2 provisioning. |
| 5. Progressive `k6` Load Testing (`smoke`–`10,000` VUs) | **NOT TESTED** | Depends on Step 2 provisioning (`k6` not installed). |
| 6. Existing Automated Test Suite (`php artisan test --compact`) | **VERIFIED** | `489` passed, `0` failed, `0` skipped, `3,437` assertions (`53,998 ms`). |
