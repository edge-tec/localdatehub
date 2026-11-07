# LocalDateHub Setup & Environment Configuration Guide

**Version:** 1.0.0  
**Phase:** Phase 01 (Foundation & Enterprise Architecture)

---

## 1. System Requirements

Ensure the host or development environment meets the following specifications:
- **PHP:** `^8.3` (Audited on `PHP 8.5.6`)
- **PHP Extensions:** `pdo_mysql`, `mbstring`, `openssl`, `gd`, `curl`, `xml`, `tokenizer`
- **Node.js:** `^20.0` or `^22.0` (Audited on `v22.22.0`)
- **NPM:** `^10.0` (Audited on `10.9.4`)
- **Composer:** `^2.7` (Audited on `2.10.0`)
- **Database:** MySQL 8.0+ / 8.4+ with GIS spatial engine (or SQLite for isolated testing)
- **Cache & Queue:** Redis 7.x (or database driver for local development)

---

## 2. Initial Setup Steps

### 2.1 Clone and Environment Initialization
```bash
# Clone the repository
git clone <repo-url> localdatehub
cd localdatehub

# Copy the environment file
cp .env.example .env

# Generate application encryption key
php artisan key:generate
```

### 2.2 Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install NPM dependencies
npm install

# Compile frontend assets
npm run build
```

---

## 3. Database Configuration

In `.env`, configure the MySQL database connection:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=localdatehub
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

Create the database in MySQL:
```sql
CREATE DATABASE IF NOT EXISTS localdatehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Run database migrations:
```bash
php artisan migrate
```

---

## 4. Running the Development Environment

Start the full development stack (Laravel server + Vite asset watcher + queue listener):
```bash
composer run dev
```

Or run services individually:
```bash
# Terminal 1: Backend PHP Server
php artisan serve

# Terminal 2: Vite Hot Module Reloading (HMR)
npm run dev

# Terminal 3: Background Queue Worker
php artisan queue:listen
```

---

## 5. Running Automated Tests

LocalDateHub uses automated Pest / PHPUnit test suites:

```bash
# Run all tests
php artisan test

# Run only feature tests
php artisan test tests/Feature

# Run only unit tests
php artisan test tests/Unit
```

---

## 6. Health & Diagnostic Probes

The application provides standardized health endpoints:

### Liveness Probe (Public)
Used by load balancers, Kubernetes, and uptime monitoring:
```bash
curl http://localhost:8000/health
```
**Response:**
```json
{
  "success": true,
  "status": 200,
  "message": "LocalDateHub service is online",
  "data": {
    "status": "healthy",
    "timestamp": "2026-11-08T00:00:00+00:00",
    "app": "LocalDateHub",
    "env": "local"
  }
}
```

### Readiness & Dependency Diagnostic Probe (Guarded)
Requires the `X-Health-Key` header matching `LOCALDATEHUB_HEALTH_CHECK_SECRET`:
```bash
curl -H "X-Health-Key: local-development-health-key" http://localhost:8000/health/detailed
```
**Response:**
```json
{
  "success": true,
  "status": 200,
  "message": "All system dependencies are healthy",
  "data": {
    "status": "healthy",
    "checks": {
      "database": { "status": "healthy", "latency_ms": 1.2, "connection": "mysql" },
      "cache": { "status": "healthy", "latency_ms": 0.8, "store": "database" },
      "storage": { "status": "healthy", "disk": "local" },
      "system": { "status": "healthy", "php_version": "8.5.6", "memory_usage_mb": 18.5 }
    }
  }
}
```
