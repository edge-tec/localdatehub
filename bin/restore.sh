#!/usr/bin/env bash
# ==============================================================================
# LOCALADZONE — DATABASE RESTORE AND RECOVERY SCRIPT
# Restores a compressed SQL dump into target database with checksum verification
# ==============================================================================

set -eo pipefail

RESTORE_FILE="$1"

if [ -z "${RESTORE_FILE}" ] || [ ! -f "${RESTORE_FILE}" ]; then
    echo "ERROR: Specify a valid gzipped SQL backup file to restore."
    echo "Usage: ./bin/restore.sh /path/to/localadzone_db_YYYYMMDD_HHMMSS.sql.gz"
    exit 1
fi

# Verify Checksum if present
if [ -f "${RESTORE_FILE}.sha256" ]; then
    echo ">>> Verifying SHA-256 checksum..."
    sha256sum -c "${RESTORE_FILE}.sha256"
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-localadzone}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

echo "================================================================="
echo " CRITICAL DATABASE RESTORATION"
echo " Target Database: ${DB_DATABASE} on ${DB_HOST}"
echo " Source File:     ${RESTORE_FILE}"
echo "================================================================="

read -r -p "Are you sure you want to completely overwrite '${DB_DATABASE}'? [y/N] " CONFIRM
if [[ ! "$CONFIRM" =~ ^[yY]$ ]]; then
    echo "Restoration aborted by operator."
    exit 0
fi

echo ">>> Decompressing and importing SQL backup..."
gunzip < "${RESTORE_FILE}" | mysql \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    --password="${DB_PASSWORD}" \
    --default-character-set=utf8mb4 \
    "${DB_DATABASE}"

echo ">>> Database restored successfully at $(date)."
echo ">>> Clearing application cache..."
php artisan optimize:clear || true
