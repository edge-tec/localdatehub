# LOCALADZONE — PRODUCTION ENVIRONMENT VARIABLES SPECIFICATION

This document details every environment variable used in LocalAdZone, its security classification, expected format, and operational role.

---

## 1. Application & Security Configuration

| Variable | Type | Secret? | Description & Recommended Production Value |
|---|---|:---:|---|
| `APP_NAME` | String | No | Application branding (`LocalAdZone`). |
| `APP_ENV` | String | No | Set strictly to `production`. |
| `APP_KEY` | String (Base64) | **CRITICAL** | 32-byte AES encryption key generated via `php artisan key:generate`. |
| `APP_DEBUG` | Boolean | No | Must be `false`. Never enable in production. |
| `APP_URL` | URL | No | Canonical HTTPS domain (`https://localadzone.com`). |
| `APP_MAINTENANCE_DRIVER`| String | No | `cache` (Allows maintenance mode across multiple web nodes). |

---

## 2. Database (MySQL 8)

| Variable | Type | Secret? | Description |
|---|---|:---:|---|
| `DB_CONNECTION` | String | No | `mysql` |
| `DB_HOST` | String | No | Hostname or IP of primary database server (`127.0.0.1`). |
| `DB_PORT` | Integer | No | `3306` |
| `DB_DATABASE` | String | No | Production database name (`localadzone_prod`). |
| `DB_USERNAME` | String | Yes | Dedicated database user (`localadzone_user`). |
| `DB_PASSWORD` | String | **CRITICAL** | High-entropy alphanumeric password. |

---

## 3. Redis (Cache, Sessions, Queues)

| Variable | Type | Secret? | Description |
|---|---|:---:|---|
| `CACHE_STORE` | String | No | `redis` |
| `SESSION_DRIVER` | String | No | `redis` |
| `SESSION_LIFETIME` | Integer | No | `120` (minutes). |
| `SESSION_ENCRYPT` | Boolean | No | `true` (Protects session payload with AES-256). |
| `SESSION_SECURE_COOKIE`| Boolean | No | `true` (Ensures cookies transmitted only over HTTPS). |
| `QUEUE_CONNECTION` | String | No | `redis` |
| `REDIS_HOST` | String | No | `127.0.0.1` |
| `REDIS_PASSWORD` | String | **CRITICAL** | Redis authentication password (`requirepass`). |
| `REDIS_CACHE_DB` | Integer | No | `0` (Isolated cache partition). |
| `REDIS_SESSION_DB`| Integer | No | `1` (Isolated session partition). |
| `REDIS_QUEUE_DB` | Integer | No | `2` (Isolated queue partition). |

---

## 4. Object Storage & Media (AWS S3 / Cloudflare R2)

| Variable | Type | Secret? | Description |
|---|---|:---:|---|
| `FILESYSTEM_DISK` | String | No | `s3` (or `local` for single-node private storage). |
| `AWS_ACCESS_KEY_ID` | String | Yes | IAM User / API key with write permissions to media bucket. |
| `AWS_SECRET_ACCESS_KEY`| String | **CRITICAL** | Secret key for object storage access. |
| `AWS_DEFAULT_REGION` | String | No | Storage region (e.g., `us-east-1`). |
| `AWS_BUCKET` | String | No | S3 bucket name (`localadzone-media-production`). |
| `AWS_URL` | URL | No | CDN delivery domain (e.g. `https://media.localadzone.com`). |

---

## 5. Transactional Email (Postmark / SES / Mailgun)

| Variable | Type | Secret? | Description |
|---|---|:---:|---|
| `MAIL_MAILER` | String | No | `smtp` |
| `MAIL_HOST` | String | No | `smtp.postmarkapp.com` (or AWS SES endpoint). |
| `MAIL_PORT` | Integer | No | `587` |
| `MAIL_USERNAME` | String | Yes | SMTP user or Postmark server token. |
| `MAIL_PASSWORD` | String | **CRITICAL** | SMTP secret password or server token. |
| `MAIL_ENCRYPTION` | String | No | `tls` |
| `MAIL_FROM_ADDRESS` | Email | No | Official sender address (`no-reply@localadzone.com`). |
| `MAIL_FROM_NAME` | String | No | Display name (`LocalAdZone Notifications`). |

---

## 6. Adult Monetization & Payment Gateways

| Variable | Type | Secret? | Description |
|---|---|:---:|---|
| `MONETIZATION_GATEWAY` | String | No | Active default gateway: `ccbill`, `stripe-adult`, or `sandbox`. |
| `CCBILL_ACCOUNT_NUMBER`| String | Yes | CCBill 6-digit client account number. |
| `CCBILL_SUBACCOUNT` | String | Yes | CCBill 4-digit subaccount number. |
| `CCBILL_SALT` | String | **CRITICAL** | Secret encryption salt for MD5 verification of dynamic pricing and webhooks. |
| `CCBILL_FLEXFORM_ID` | String | No | ID of configured FlexForm checkout page. |
| `STRIPE_ADULT_KEY` | String | Yes | Public publishable key (if approved by adult acquiring partner). |
| `STRIPE_ADULT_SECRET` | String | **CRITICAL** | Secret API key. |
| `STRIPE_ADULT_WEBHOOK_SECRET`| String | **CRITICAL** | HMAC-SHA256 signature secret for inbound webhooks. |

---

## 7. Compliance, Monitoring & Health Check

| Variable | Type | Secret? | Description |
|---|---|:---:|---|
| `AGE_ASSURANCE_PROVIDER` | String | No | Integrated age verification vendor (`verisoul`, `yoti`). |
| `AGE_ASSURANCE_API_KEY` | String | **CRITICAL** | API token for remote age verification provider. |
| `ADMIN_MFA_REQUIRED` | Boolean | No | Must be `true` in production to enforce 2FA on admin portal. |
| `HEALTH_CHECK_SECRET` | String | Yes | Secret Bearer token for accessing `/api/health` without rate limiting. |
| `SENTRY_LARAVEL_DSN` | URL | Yes | Sentry crash reporting DSN. |

