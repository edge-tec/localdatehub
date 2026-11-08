# LOCALADZONE — BACKUP & DISASTER RECOVERY GUIDE

**Objective:** Ensure complete business continuity, point-in-time recovery, and zero data loss for adult personal classified listings, invoices, and compliance logs.

---

## 1. Backup Strategy & Cadence

| Data Category | Target | Frequency | Retention | Tool / Script |
|---|---|---|---|---|
| **Transactional Database** | MySQL (`localadzone_prod`) | Daily at 02:00 UTC (Full) + Hourly Binlogs | 14 Days Local, 90 Days S3 Glacier | `./bin/backup.sh` |
| **User Media (Images)** | Object Storage (S3 / R2) | Continuous (Versioned & Cross-Region Replicated) | Permanent / Governed by Retention Policy | Cloud Provider Replication |
| **Server Configuration** | Nginx, Supervisor, PHP, Crontab | On deployment / Weekly | Git repository / Encrypted Archive | Git / Ansible |

---

## 2. Automated Database Backup (`bin/backup.sh`)

The automated script performs a lock-free InnoDB dump using `--single-transaction`, pipes output through maximum gzip compression (`gzip -9`), calculates a cryptographic SHA-256 checksum, uploads the archive to off-site cloud storage, and prunes archives older than 14 days.

### Execution via Crontab:
```bash
# Edit root or deploy crontab
sudo crontab -e

# Run backup daily at 02:00 AM UTC
0 2 * * * /var/www/localadzone/bin/backup.sh >> /var/log/localadzone-backup.log 2>&1
```

---

## 3. Database Restoration Runbook (`bin/restore.sh`)

### Pre-Restoration Verification:
1. Ensure the application is placed into maintenance mode:
   ```bash
   php artisan down --message="System maintenance in progress. Performing recovery."
   ```
2. Verify available disk space on the target database partition:
   ```bash
   df -h /var/lib/mysql
   ```
3. Locate the designated backup archive and verify its checksum:
   ```bash
   sha256sum -c /var/backups/localadzone/localadzone_db_20261108_020000.sql.gz.sha256
   ```

### Executing Recovery:
Run the restoration automation:
```bash
sudo ./bin/restore.sh /var/backups/localadzone/localadzone_db_20261108_020000.sql.gz
```
The script will prompt for manual confirmation, unpack the compressed archive, execute prepared statements, restore triggers and stored procedures, and clear the application cache.

### Post-Restoration Sanity Checks:
```bash
# 1. Verify table counts and latest records
mysql -u localadzone_user -p localadzone_prod -e "SELECT count(*) FROM listings; SELECT count(*) FROM users; SELECT count(*) FROM invoices;"

# 2. Clear and rebuild application caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 3. Bring application online
php artisan up
```

---

## 4. Disaster Recovery Scenarios

### Scenario A: Accidental Dropped Table / Bad Migration
1. Activate maintenance mode.
2. Restore the previous night's full backup to a temporary database (`localadzone_temp`).
3. Extract the affected table:
   ```bash
   mysqldump localadzone_temp listings > /tmp/listings_recovered.sql
   mysql localadzone_prod < /tmp/listings_recovered.sql
   ```
4. Re-apply any missing migrations without the destructive step.

### Scenario B: Complete Server Loss (Cold Site Recovery)
1. Provision a new server instance via `PRODUCTION_DEPLOYMENT_GUIDE.md`.
2. Retrieve the latest backup and `.env` from secure off-site cloud storage.
3. Execute `./bin/restore.sh` to restore schema and state.
4. Update DNS records (Cloudflare) to point to the new server IP.
5. Deactivate maintenance mode. Recovery Time Objective (RTO): **< 45 minutes**. Recovery Point Objective (RPO): **< 1 hour**.

