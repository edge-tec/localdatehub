# LocalAdZone — Data Retention & Evidence Preservation Policy

**Document ID:** COMP-DOC-RET-001  
**Regulatory Standards:** 18 U.S.C. § 2703(f), CCPA/CPRA (Cal. Civ. Code § 1798.100), GDPR, 18 U.S.C. § 2257  
**Effective Date:** November 2026  

---

## 1. Objective and Scope

This document defines the lifecycle, retention periods, legal hold freezes, and secure destruction procedures for all user records, listing content, media assets, audit logs, and compliance records across LocalAdZone.

---

## 2. Standard Retention Schedules

| Data Category | Primary Storage | Retention Period | Destruction / Purge Method |
| :--- | :--- | :--- | :--- |
| **Active User Account Data** | MySQL Primary DB | Life of account + 30 days grace | Soft-delete followed by automated crypto-erasure. |
| **Deleted User Account Data** | MySQL Soft-Deletes | 30 days (for rollback / dispute) | Automated batch purge of database rows and CDN assets. |
| **Active Listings & Photos** | MySQL / Local Storage / S3 | Active listing lifetime + 60 days | Expiration cron transitions status to `expired`; storage files purged. |
| **Quarantined / Rejected Content** | `moderation_queue` / Cold DB | 180 days | Reviewed or archived to cold compliance storage. |
| **Administrative Audit Logs** | `admin_audit_logs` | 365 days (1 year) | Immutable append-only log; archived to Glacier/Coldline. |
| **DMCA Notices & Counter-Notices** | `dmca_requests` | 3 years | Statutory safe-harbor record maintenance. |
| **Law Enforcement Requests** | `legal_requests` | 7 years | Statutory statute of limitations / federal audit window. |
| **18 U.S.C. § 2257 Records** | `evidence_preservations` | 7 years or lifetime of depiction | Encrypted cold storage vault. |

---

## 3. Legal Hold & Evidence Preservation (18 U.S.C. § 2703(f))

### 3.1 Overview of 18 U.S.C. § 2703(f)
Under the Stored Communications Act (18 U.S.C. § 2703(f)), upon the request of a governmental entity, LocalAdZone is legally obligated to take all necessary steps to preserve records and other evidence in its possession pending the issuance of a court order, subpoena, or search warrant. Preservation is mandatory for 90 days, renewable for an additional 90 days upon official request.

### 3.2 Evidence Freezing Engine: `EvidencePreservation`
When a legal request, subpoena, or urgent safety investigation is opened:
1. `ComplianceService::freezeEvidenceForLegalRequest($legalRequest, $user, $listing, $reason, $adminId)` is invoked.
2. An immutable snapshot of the user record, current profile, listing content, photos, IP logs, and moderation history is captured into the `evidence_preservations` table.
3. The preservation record is assigned a status of `active` with an auto-calculated expiration date (default: 90 days).
4. A hash signature (`SHA-256`) is generated for evidentiary chain-of-custody integrity.

```sql
CREATE TABLE evidence_preservations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    listing_id BIGINT UNSIGNED NULL,
    legal_request_id BIGINT UNSIGNED NULL,
    preservation_reason VARCHAR(255) NOT NULL,
    evidence_snapshot JSON NOT NULL,
    status VARCHAR(30) DEFAULT 'active', -- active, released, expired
    expires_at TIMESTAMP NULL,
    ...
);
```

---

## 4. Conflict Resolution: Privacy Erasure (CCPA/GDPR) vs. Legal Holds

Under California Consumer Privacy Act (CCPA Cal. Civ. Code § 1798.105(d)) and GDPR Article 17(3)(e), an organization is exempt from complying with a consumer's request for erasure if retaining the personal data is necessary to comply with a legal obligation, defend against legal claims, or adhere to federal preservation mandates.

### 4.1 Erasure Guardrail in LocalAdZone
Before any user account deletion or listing destruction is executed (via User Settings `/account/security` or Admin Portal):
1. The platform executes `ComplianceService::canEraseUserData($user)`:
   ```php
   public function canEraseUserData(User $user): bool
   {
       $activeHold = EvidencePreservation::where('user_id', $user->id)
           ->where('status', 'active')
           ->where(function ($q) {
               $q->whereNull('expires_at')
                 ->orWhere('expires_at', '>', now());
           })
           ->exists();

       return ! $activeHold;
   }
   ```
2. If `canEraseUserData()` returns `false`, deletion is **aborted**:
   - The user receives an error notice: *"Account deletion cannot be processed at this time due to active legal or compliance hold requirements."*
   - An entry is logged in `admin_audit_logs`.
   - Data records remain frozen and immutable until the legal hold is formally released by the Legal Operations team.

---

## 5. Formal Hold Release Workflow

Only designated Legal Compliance Administrators may release an active preservation hold.
- Admin enters `admin/enterprise` -> `Evidence Vault`.
- Admin reviews case details and clicks `Release Hold`.
- Admin inputs mandatory release justification (e.g., *"Case closed by Assistant U.S. Attorney, no indictment filed"*).
- System transitions status to `released`, records the releasing admin ID and timestamp, and notifies audit streams.

