# LocalDateHub — Multi-Chain Mainnet Readiness & Production Deployment Runbook

## 1. Supported Mainnet Matrix (`MainnetBlockchainNetworkSeeder`)
All mainnet networks are seeded with `is_enabled = false` and blocked from activation while `CUSTODY_TESTNET_ONLY=true`.

| Network Code | Chain ID | Native | Verified Token Contracts | Confirmations |
|---|---:|---|---|---:|
| `ETH_MAINNET` | 1 | ETH (18) | USDT (`0xdAC17F958D2ee523a2206206994597C13D831ec7`, 6), USDC (`0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48`, 6) | 12 |
| `BSC_MAINNET` | 56 | BNB (18) | USDT (`0x55d398326f99059fF775485246999027B3197955`, 18), USDC (`0x8AC76a51cc950d9822D68b83fE1Ad97B32Cd580d`, 18) | 15 |
| `POLYGON_MAINNET` | 137 | POL (18) | USDT (`0xc2132D05D31c914a87C6611C10748AEb04B58e8F`, 6), USDC (`0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359`, 6) | 32 |
| `ARBITRUM_MAINNET` | 42161 | ETH (18) | USDT (`0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9`, 6), USDC (`0xaf88d065e77c8cC2239327C5EDb3A432268e5831`, 6) | 20 |
| `BASE_MAINNET` | 8453 | ETH (18) | USDC (`0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913`, 6) | 20 |

---

## 2. Mainnet Readiness Checklist (Pre-Launch Gate)
1. **Fresh Production Secrets (Never reuse staging/testnet keys):**
   - Generate a brand-new 12/24-word `SIGNER_DEPOSIT_MNEMONIC` offline/inside the isolated production signer host and back it up in encrypted offline cold storage.
   - Generate a dedicated `SIGNER_GAS_SPONSOR_PK` and fund it with small gas budgets (`ETH` / `BNB` / `POL`) for ERC-20/BEP-20 sweeps.
   - Generate a fresh 64-char `SIGNER_HMAC_SECRET` / `HSM_HTTP_HMAC_SECRET`.
2. **Database Backup:**
   - Take a full snapshot before running `php artisan migrate --force` and `php artisan db:seed --class=MainnetBlockchainNetworkSeeder`.
3. **Production Signer (`127.0.0.1:8590`) Configuration:**
   - Set `SIGNER_ALLOWED_CHAINS` to only explicitly approved mainnet chain IDs (e.g., `1,56,137,42161,8453`).
   - Set `SIGNER_ALLOWED_TREASURIES` to your verified cold/Trust Wallet mainnet receiving addresses.
4. **Laravel `.env` Configuration:**
   - `CUSTODY_DRIVER=hsm_http`
   - `CUSTODY_TESTNET_ONLY=false` (only after owner sign-off)
   - `HSM_HTTP_BASE_URL=http://127.0.0.1:8590`
   - `HSM_HTTP_HMAC_SECRET=<production-hmac-secret>`
5. **Verified RPCs & Treasury Allowlist:**
   - Update `blockchain_networks.rpc_configuration_reference` with paid/redundant Alchemy or QuickNode mainnet endpoints + failover URLs.
   - Insert verified `treasury_wallets` rows (`is_allowlisted = true`, `is_testnet = false`, `status = 'ACTIVE'`).
6. **Daemons & Monitoring:**
   - PM2 running `signer-service/server.mjs` (`pm2 save && pm2 startup`).
   - Supervisor running `queue:work --queue=default,monitoring,verification,crediting,sweeping,recovery --sleep=3 --tries=3 --max-time=3600`.
   - Cron running `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1` (`custody:scan-networks` every minute, `custody:health-check` every 5 minutes).

---

## 3. Rollback & Emergency Recovery Procedures
1. **Immediate Outbound Freeze (No SSH needed):**
   - Admin Panel: `/admin/crypto/treasury` → **Emergency Pause Sweeps** (`POST /admin/crypto/treasury/pause`).
   - Or CLI: `php artisan tinker --execute="app(App\Services\Security\TransactionPolicy::class)->pauseOutbound();"`
2. **Hard Signer Kill-Switch:**
   - Set `SIGNER_EMERGENCY_SHUTDOWN=true` in `signer-service/ecosystem.config.cjs` and run `pm2 restart sepolia-signer --update-env` (returns HTTP `423` for all signing requests while keeping `/v1/health` and address derivation observable).
3. **Failed Sweep Recovery:**
   - Inspect `/admin/crypto/treasury` or `RecoveryQueueItem` table, replenish gas sponsor if needed, and trigger **Retry** (`POST /admin/crypto/treasury/sweep/{id}/retry`).
