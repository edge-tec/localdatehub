# LocalDateHub — Phase 4 Production Readiness & Blockers

## 1. Readiness Summary
- **Code & Automated Tests:** Ready (`VERIFIED` in local test suite).
- **Production Deployment:** Disabled / Strictly prohibited without explicit authorization.

## 2. Infrastructure Dependencies & Unresolved Blockers
1. **Redis Server + `phpredis`:** Must be installed and secured on staging/production (`CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`).
2. **Supervisor / Laravel Horizon:** Must be activated on server (`docs/supervisor/localdatehub-workers.conf` and `config/horizon.php` prepared; `laravel/horizon` package optional if UI desired).
3. **Cloudflare CDN/WAF & DNS:** Requires Cloudflare account access to enable edge caching rules for static/public assets and WAF rate limiting.
4. **MySQL Read Replicas:** Required before high-concurrency read scaling (5,000–10,000+ VUs).
5. **k6 Staging Benchmarks:** Required to establish measured RPS, p95, and p99 numbers before claiming million-traffic capacity.

## 3. Recommended Next Steps (Authorized Staging First)
1. Provision staging server with Redis + `php8.5-redis` + Supervisor.
2. Deploy `main` to staging, run `php artisan migrate --force`, and start `localdatehub-core`, `localdatehub-analytics`, and `localdatehub-comms` workers.
3. Run `k6` Stage 1 (`100` VUs) -> Stage 2 (`500` VUs) -> Stage 3 (`1,000` VUs), recording actual metrics.
4. Inspect CSP `Content-Security-Policy-Report-Only` browser console output on staging before tightening `script-src`.
5. Request explicit approval before any production rollout.

