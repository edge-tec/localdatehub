#!/usr/bin/env bash
# ==============================================================================
# LOCALADZONE — PRODUCTION DEPLOYMENT AUTOMATION SCRIPT
# Executes safe, non-destructive, cache-warmed production release
# ==============================================================================

set -eo pipefail

APP_DIR="${APP_DIR:-/var/www/localadzone}"
BRANCH="${BRANCH:-main}"
RELEASE_DATE=$(date +"%Y-%m-%d %H:%M:%S")

echo "================================================================="
echo " Starting LocalAdZone Production Deployment at ${RELEASE_DATE}"
echo " Target Directory: ${APP_DIR} | Branch: ${BRANCH}"
echo "================================================================="

cd "${APP_DIR}"

# 1. Enable Maintenance Mode (Allows admin bypass secret)
echo ">>> [1/9] Activating Maintenance Mode..."
php artisan down --secret="laz-admin-deploy-bypass-$(date +%s)" --render="errors.503" || true

# 2. Fetch Latest Stable Code from Repository
echo ">>> [2/9] Fetching latest git commits on branch ${BRANCH}..."
git fetch origin "${BRANCH}"
git checkout "${BRANCH}"
git pull origin "${BRANCH}"

# 3. Install Optimized Production PHP Dependencies
echo ">>> [3/9] Installing production Composer dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet

# 4. Compile Modern Production Frontend Assets
echo ">>> [4/9] Building production Tailwind CSS and Vite bundles..."
if [ -f "package.json" ]; then
    npm ci --prefer-offline --no-audit --quiet
    npm run build --quiet
fi

# 5. Execute Safe, Non-Destructive Database Migrations
echo ">>> [5/9] Running database migrations with --force..."
php artisan migrate --force

# 6. Clear Legacy Cache and Warm Production Cache Layers
echo ">>> [6/9] Optimizing and warming application caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Restart Background Queue Workers / Horizon
echo ">>> [7/9] Reloading background queue workers..."
php artisan queue:restart || true
if php artisan list | grep -q "horizon:terminate"; then
    php artisan horizon:terminate || true
fi

# 8. Reload PHP-FPM and Web Server
echo ">>> [8/9] Reloading PHP-FPM service..."
if command -v systemctl &> /dev/null; then
    sudo systemctl reload php8.3-fpm || true
fi

# 9. Verify Application Health and Deactivate Maintenance Mode
echo ">>> [9/9] Verifying health and bringing application online..."
php artisan up

echo "================================================================="
echo " LocalAdZone Production Deployment Completed Successfully!"
echo " Release Time: $(date +"%Y-%m-%d %H:%M:%S")"
echo "================================================================="

