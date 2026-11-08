#!/usr/bin/env bash
# ==============================================================================
# LOCALADZONE — AUTOMATED DATABASE BACKUP SCRIPT
# Performs consistent mysqldump with gzip compression and retention pruning
# ==============================================================================

set -eo pipefail

BACKUP_DIR="${BACKUP_DIR:-/var/backups/localadzone}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/localadzone_db_${TIMESTAMP}.sql.gz"
RETENTION_DAYS=14

mkdir -p "${BACKUP_DIR}"

# Extract DB credentials from .env if not explicitly set in environment
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-localadzone}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

echo ">>> Starting database backup for [${DB_DATABASE}] at $(date)..."

# Perform consistent mysqldump (single-transaction for InnoDB to avoid table locks)
mysqldump \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    --password="${DB_PASSWORD}" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --default-character-set=utf8mb4 \
    "${DB_DATABASE}" | gzip -9 > "${BACKUP_FILE}"

# Generate SHA-256 Checksum for integrity verification
sha256sum "${BACKUP_FILE}" > "${BACKUP_FILE}.sha256"

echo ">>> Backup saved to: ${BACKUP_FILE}"
echo ">>> Checksum: $(cat "${BACKUP_FILE}.sha256")"

# Optional: Upload to S3/Cloud Storage if AWS CLI is configured
if command -v aws &> /dev/null && [ -n "${BACKUP_S3_BUCKET}" ]; then
    echo ">>> Syncing backup to AWS S3: s3://${BACKUP_S3_BUCKET}/backups/"
    aws s3 cp "${BACKUP_FILE}" "s3://${BACKUP_S3_BUCKET}/backups/$(basename "${BACKUP_FILE}")"
    aws s3 cp "${BACKUP_FILE}.sha256" "s3://${BACKUP_S3_BUCKET}/backups/$(basename "${BACKUP_FILE}").sha256"
fi

# Prune backups older than retention policy
echo ">>> Pruning local backups older than ${RETENTION_DAYS} days..."
find "${BACKUP_DIR}" -name "localadzone_db_*.sql.gz*" -mtime +"${RETENTION_DAYS}" -delete

echo ">>> Backup process completed successfully at $(date)."

