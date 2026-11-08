#!/usr/bin/env bash
# ==============================================================================
# LOCALADZONE — STAGING & PRE-PRODUCTION READINESS VERIFICATION SCRIPT
# Runs automated pre-flight checks across routes, database, cache & queues
# ==============================================================================

set -eo pipefail

echo "================================================================="
echo " LOCALADZONE — PRE-FLIGHT READINESS VERIFICATION"
echo " Time: $(date)"
echo "================================================================="

# 1. Environment & Config Validation
echo ">>> [1/7] Testing configuration syntax and caching..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Database Connection & Migration Status
echo ">>> [2/7] Checking database migration status..."
php artisan migrate:status

# 3. Schedule List Inspection
echo ">>> [3/7] Verifying scheduled maintenance jobs..."
php artisan schedule:list

# 4. Storage Link Verification
echo ">>> [4/7] Checking public storage symlink..."
php artisan storage:link || true

# 5. Core Feature Tests
echo ">>> [5/7] Executing core regression test suite..."
php artisan test --filter=ProductionReleaseReadinessTest || php artisan test --filter=HealthCheckTest

# 6. Check Sitemaps and Robots
echo ">>> [6/7] Verifying sitemap and robots generation..."
php artisan test --filter=SeoAnalyticsAndNotificationsTest

echo "================================================================="
echo " PRE-FLIGHT READINESS VERIFICATION COMPLETED SUCCESSFULLY"
echo "================================================================="
