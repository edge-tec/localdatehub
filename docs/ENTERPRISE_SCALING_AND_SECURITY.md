# LocalDateHub Enterprise Security & Million-Traffic Scaling Architecture

## 1. Target Infrastructure Topology
```text
Internet
  -> Cloudflare WAF / CDN (DDoS protection, Bot Fight Mode, Edge Cache for static & anonymous directory pages)
  -> Load Balancer (Nginx / HAProxy / Cloud LB with healthcheck GET /up)
  -> Stateless Application Nodes (Multiple PHP 8.3+ / PHP-FPM or Laravel Octane Swoole/FrankenPHP nodes)
  -> Redis Cluster / Dedicated Redis (Cache, Sessions, Rate Limiting, Queues)
  -> Dedicated Queue Workers (Supervisor managing `critical`, `emails`, `analytics`, `sweeping`, `default`)
  -> MySQL 8.0 Primary (Transactional writes: wallet, deposits, ledger, auth, listings)
  -> MySQL 8.0 Read Replicas (Search, directory listings, analytics reporting)
  -> S3 / Cloudflare R2 + CDN (User media, listing photos, static assets)
```

## 2. Redis, Session & Queue Configuration
Set in staging/production `.env` (never commit secrets):
```ini
APP_DEBUG=false
SESSION_DRIVER=redis
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=redis
QUEUE_CONNECTION=redis
ANALYTICS_ASYNC_ENABLED=true
ANALYTICS_QUEUE=analytics
```

### Supervisor Worker Pools (`/etc/supervisor/conf.d/localdatehub-workers.conf`)
- **Critical / Payments / Sweeps:** `php artisan queue:work redis --queue=critical,sweeping,default --sleep=1 --tries=3 --max-time=3600 --memory=256` (4+ processes)
- **Analytics Telemetry (`RecordPlatformTrafficJob`):** `php artisan queue:work redis --queue=analytics --sleep=1 --tries=3 --backoff=5 --max-time=3600 --memory=256` (4–8 processes)
- **Emails & Notifications:** `php artisan queue:work redis --queue=emails,notifications --sleep=2 --tries=3 --max-time=3600 --memory=256` (2–4 processes)

> Note: `laravel/horizon` is not currently installed in `composer.json`. Install `laravel/horizon` if you want a Redis queue dashboard UI (`php artisan horizon:install`), or run the Supervisor pools above immediately.

## 3. PHP-FPM & OPcache Tuning (Production Node Baseline)
- `memory_limit = 256M` (or `512M` for heavy admin/export workers; resolves the previously observed 128MB exhaustion)
- `opcache.enable=1`, `opcache.memory_consumption=256`, `opcache.max_accelerated_files=20000`, `opcache.validate_timestamps=0` (clear on deploy)
- `pm = dynamic`, `pm.max_children` sized to `(Available RAM - OS/Redis/MySQL) / ~60MB per PHP-FPM worker`
- Deploy optimizations:
  `composer install --no-dev --optimize-autoloader`
  `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## 4. Cloudflare CDN & Caching Rules
- **Static Assets (`/build/*`, `/images/*`):** Cache Everything, Edge TTL 1 month+ (Vite hashes filenames).
- **Private / Dynamic / CSRF Routes (`/account*`, `/admin*`, `/checkout*`, `/contact*`, `/login`, `/register`, `/api*`, `/webhooks*`):** Bypass Cache (`Cache-Control: private, no-store, no-cache, must-revalidate` enforced by `SecurityHeadersMiddleware`).
- **Anonymous Public Pages:** Bypass cache whenever `laravel_session` cookie is present.

## 5. Production Deployment & Rollback Safeguards
1. Verify disk (`df -h`), RAM (`free -m`), Redis (`redis-cli ping`), and fresh MySQL backup before deploying.
2. Pull & test in **staging** first; run `php artisan test`.
3. On production:
   ```bash
   cd /www/wwwroot/localdatehub.com
   php artisan down --retry=60
   git pull origin main
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan optimize:clear
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   php artisan queue:restart
   php artisan up
   ```
4. **Rollback:** `git checkout <previous-commit> && php artisan migrate:rollback --step=1` (only if migration was applied), re-run cache commands, and `php artisan queue:restart`.

