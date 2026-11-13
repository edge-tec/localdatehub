# LocalDateHub — Phase 8 Staging / Host Environment Audit

**Date:** 2026-11-14  
**Host Audited:** Local macOS ARM64 development workstation (`Darwin Mizanurs-Mac-mini.local 27.2.0 arm64`) — remote staging server unavailable.

---

## 1. Hardware, OS & Runtime Inventory

| Component | Measured Value |
| :--- | :--- |
| **OS / Kernel** | `Darwin 27.2.0` (`RELEASE_ARM64_T8132 arm64`) |
| **CPU Cores** | `10` |
| **Total RAM** | `17,179,869,184` bytes (`16 GiB`) |
| **Workspace Disk (`/Volumes/EDGE`)** | `931 GiB` total, `144 GiB` used, `786 GiB` available (`16%`) |
| **Root Disk (`/`)** | `228 GiB` total, `13 GiB` used, `2.3 GiB` available (`85%`) |
| **PHP Version** | `PHP 8.5.6 (cli)` with `Zend OPcache v8.5.6`, `pdo_mysql`, `mysqli`, `pdo_sqlite`, `dom`, `libxml`, `pcntl` |
| **Laravel Version** | `Laravel Framework 13.40.0` |
| **MySQL Server** | `26.7.0` (`mysqld` PID `2009` listening on TCP `*:3306`, `*:33060`, `max_connections = 151`) |
| **Redis Server / `redis-cli`** | **NOT INSTALLED** (`redis-server not found`, `redis-cli not found`) |
| **PHP Redis (`phpredis` / `predis`)** | **NOT INSTALLED** |
| **Laravel Horizon (`laravel/horizon`)** | **NOT INSTALLED** in `composer.json` |
| **Supervisor (`supervisorctl`)** | **NOT INSTALLED** |
| **k6 Load Runner (`k6`)** | **NOT INSTALLED** |
| **Web Servers (`nginx`/`apache2`/`caddy`)** | **NOT INSTALLED** locally |

---

## 2. Shared-Host & Port Conflict Findings

- Active TCP listeners observed on host: `5432` (`postgres`), `3306`/`33060` (`mysqld`), `5000`/`7000` (`ControlCe`), `8591` (`node`), plus IDE/system ports.
- Multiple unrelated databases exist on local `mysqld` (`affscash`, `ewywy35`, `jugajug_*`, `shopzone_db`, `test_store_db`, `valid_company_db`). All Phase 8 MySQL tests were strictly isolated in `localdatehub_phase8_test` and `localdatehub_phase8_restore_test`, and both temporary databases were dropped after verification.
