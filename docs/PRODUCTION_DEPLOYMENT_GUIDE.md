# LOCALADZONE — PRODUCTION DEPLOYMENT GUIDE

**Version:** 1.0 (Production Release)  
**Target Environment:** Linux Ubuntu 22.04 / 24.04 LTS (x86_64 or ARM64)  
**Stack Components:** PHP 8.3/8.4-FPM, Nginx, MySQL 8, Redis 7, Meilisearch, Supervisor  
**Security Tier:** Hardened Adult Classifieds Architecture  

---

## 1. Production Architecture Overview

LocalAdZone is deployed as a high-performance Modular Monolith designed for US-wide classified listings traffic:
- **Web Tier:** Nginx reverse proxy terminating TLS 1.3, enforcing HTTP Strict Transport Security (HSTS), rate limiting brute-force attempts, and serving versioned static assets.
- **Application Tier:** PHP 8.3/8.4-FPM processes optimized with Zend OPcache, executing Laravel 13 with compiled routes, configurations, events, and views.
- **Data Tier:** MySQL 8 with InnoDB engine, utf8mb4 character set, and specialized composite indexes for geographic and category filtering.
- **Cache & Sessions Tier:** Redis instance utilizing discrete database namespaces (DB 0: Application Cache, DB 1: Encrypted Sessions, DB 2: Queues).
- **Queue Workers:** Managed by Supervisor daemon across four prioritized queues: `high`, `billing`, `media`, `notifications`, `default`.
- **Search Tier:** Meilisearch node providing sub-15ms full-text keyword discovery.

---

## 2. Server Prerequisites & Package Installation

On a clean Ubuntu LTS server:

```bash
# Update and install core utilities
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip supervisor nginx redis-server \
    mysql-server software-properties-common ca-certificates lsb-release

# Add Ondřej Surý PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 and mandatory extensions
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql \
    php8.3-redis php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip \
    php8.3-bcmath php8.3-intl php8.3-gd php8.3-imagick

# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js & npm (LTS)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 3. Web Server & PHP-FPM Provisioning

### 3.1 Nginx Hardening
1. Copy `deployment/nginx/localadzone.conf` to `/etc/nginx/sites-available/localadzone.conf`.
2. Link the site:
   ```bash
   sudo ln -s /etc/nginx/sites-available/localadzone.conf /etc/nginx/sites-enabled/
   sudo rm -f /etc/nginx/sites-enabled/default
   ```
3. Acquire Let's Encrypt SSL certificates:
   ```bash
   sudo apt install -y certbot python3-certbot-nginx
   sudo certbot certonly --nginx -d localadzone.com -d www.localadzone.com
   ```
4. Verify syntax and reload:
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   ```

### 3.2 PHP-FPM Configuration
1. Copy `deployment/php/localadzone-php-fpm.conf` to `/etc/php/8.3/fpm/pool.d/localadzone.conf`.
2. Configure OPcache in `/etc/php/8.3/fpm/php.ini`:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=256
   opcache.interned_strings_buffer=16
   opcache.max_accelerated_files=30000
   opcache.validate_timestamps=0
   opcache.save_comments=1
   ```
3. Restart PHP-FPM:
   ```bash
   sudo systemctl restart php8.3-fpm
   ```

---

## 4. Database Setup & Initial Migration

```sql
-- Connect to MySQL
sudo mysql

-- Create database and user
CREATE DATABASE localadzone_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'localadzone_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_DATABASE_PASSWORD';
GRANT ALL PRIVILEGES ON localadzone_prod.* TO 'localadzone_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 5. Application Deployment Procedure

1. **Clone the repository to `/var/www/localadzone`:**
   ```bash
   sudo git clone https://github.com/organization/localadzone.git /var/www/localadzone
   sudo chown -R www-data:www-data /var/www/localadzone
   cd /var/www/localadzone
   ```
2. **Configure Environment:**
   ```bash
   sudo cp .env.production.example .env
   # Edit .env with real credentials
   sudo nano .env
   sudo php artisan key:generate
   ```
3. **Execute Deployment Automation:**
   ```bash
   sudo ./bin/deploy.sh
   ```
4. **Seed Reference Geographic & Category Data (First-Time Only):**
   ```bash
   sudo -u www-data php artisan db:seed --class=DatabaseSeeder --force
   ```

---

## 6. Background Queue & Scheduler Daemons

1. **Supervisor Configuration:**
   ```bash
   sudo cp deployment/supervisor/localadzone-horizon.conf /etc/supervisor/conf.d/
   sudo cp deployment/supervisor/localadzone-scheduler.conf /etc/supervisor/conf.d/
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start all
   ```
2. **Verify Daemon Status:**
   ```bash
   sudo supervisorctl status
   ```

---

## 7. Zero-Downtime Releases & Rollback Strategy

- **Routine Deployment:** Run `./bin/deploy.sh` to fetch commits, install dependencies, compile assets, run migrations with `--force`, refresh cache layers, and reload PHP-FPM.
- **Instant Rollback:** If an issue occurs, execute `./bin/rollback.sh <PREVIOUS_COMMIT_HASH>`.
- **Database Rollback:** If a migration must be reverted, inspect `bin/rollback.sh` and restore from the pre-deployment database backup created by `bin/backup.sh`.

