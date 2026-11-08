#!/usr/bin/env bash
# ==============================================================================
# LOCALADZONE — PRODUCTION ROLLBACK AUTOMATION SCRIPT
# Reverts to a previous Git commit or rolls back a single migration step
# ==============================================================================

set -eo pipefail

APP_DIR="${APP_DIR:-/var/www/localadzone}"
TARGET_COMMIT="$1"

if [ -z "${TARGET_COMMIT}" ]; then
    echo "ERROR: You must specify a target Git commit hash or tag to roll back to."
    echo "Usage: ./bin/rollback.sh <commit_hash>"
    exit 1
fi

echo "================================================================="
echo " WARNING: Initiating Rollback of LocalAdZone to ${TARGET_COMMIT}"
echo "================================================================="

cd "${APP_DIR}"

# 1. Activate Maintenance Mode
php artisan down --message="System maintenance in progress. Rolling back release."

# 2. Checkout previous commit
echo ">>> Checking out ${TARGET_COMMIT}..."
git checkout "${TARGET_COMMIT}"

# 3. Re-install vendor dependencies
echo ">>> Reinstalling composer dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet

# 4. Clear and rebuild caches
echo ">>> Rebuilding caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart Queues and PHP-FPM
php artisan queue:restart || true
if command -v systemctl &> /dev/null; then
    sudo systemctl reload php8.3-fpm || true
fi

# 6. Bring back online
php artisan up

echo "================================================================="
echo " Rollback to ${TARGET_COMMIT} Completed Successfully."
echo "================================================================="
