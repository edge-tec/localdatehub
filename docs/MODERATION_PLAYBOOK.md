# LocalAdZone — Moderation Operations Playbook (SOP)

**Audience:** Trust & Safety Agents, Tier 1/Tier 2 Content Moderators, Platform Compliance Leads  
**Effective Date:** November 2026  
**Confidentiality:** Internal Operations  

---

## 1. Operating Architecture & Queue Mechanics

The LocalAdZone Moderation Engine coordinates automated machine filters with human review queues. Every listing submission, edit, and user report is evaluated in real time:

```
[User Action / Edit]
       │
       ▼
[TextScreeningService] ──► Flags, Risk Score (0-100), Level (low/medium/high/critical)
       │
       ├─► [DuplicateDetectionService] (SHA-256 Hash & Token Similarity >= 85%)
       ├─► [AccountRiskService] (Posting Velocity, Prior Bans, Age Verification)
       │
       ▼
[Decision Engine]
       ├── Score >= 75 / Critical: Immediate Quarantine -> Urgent Queue (Urgency = 100)
       ├── Score 30-74 / Medium-High: Pending Review Queue (Urgency = 50-80)
       └── Score < 30 / Low: Auto-Approved / Clean (Post Published)
```

---

## 2. Urgency Scores & Queue Tiering

| Urgency Tier | Score Range | Primary Triggers | Service Level Agreement (SLA) |
| :--- | :--- | :--- | :--- |
| **P0 — Critical** | 90 – 100 | Suspected underage, human trafficking, non-consensual imagery, extreme threats. | **< 15 minutes** (Auto-quarantined immediately) |
| **P1 — High** | 70 – 89 | Commercial solicitation terms, explicit pricing/rates, rapid duplicate spam floods. | **< 2 hours** |
| **P2 — Medium** | 40 – 69 | Ambiguous compensation slang, contact info obfuscation, repeat account warnings. | **< 12 hours** |
| **P3 — Low** | 0 – 39 | Minor category mismatch, cosmetic violations, duplicate photo reuse. | **< 24 hours** |

---

## 3. Standard Operating Procedures (SOPs)

### 3.1 Scenario: Suspected Underage Content or References
1. **Immediate Quarantine:** System automatically suspends and hides the post.
2. **Review:** Moderator examines text, metadata, and uploaded imagery.
3. **Action:**
   - Click `Reject & Permanent Ban User`.
   - Freeze Evidence: System automatically generates an `evidence_preservations` snapshot with full user account details, IP log, and un-stripped EXIF metadata.
   - Escalate to Compliance Officer for immediate NCMEC CyberTipline referral filing.
   - Do **NOT** delete records from database. Maintain active preservation.

### 3.2 Scenario: Suspected Human Trafficking or Coercion
1. **Indicators:** Multiple ads using identical phone numbers across different states, third-party posting patterns, references to "boss", "curfew", "driver", or locked hotel rooms.
2. **Action:**
   - Execute Immediate Quarantine.
   - Mark account for `shadowban` or `permanent ban` as instructed by Compliance Lead.
   - Issue internal preservation hold under `ComplianceService::freezeEvidenceForLegalRequest()`.
   - Contact the National Human Trafficking Hotline (1-888-373-7888) or federal law enforcement if imminent physical danger is detected.

### 3.3 Scenario: Commercial Sex Solicitation (FOSTA-SESTA Violation)
1. **Indicators:** Mentions of rates, roses, donations, hourly gifts, "roses for time", "full service", or commercial escort phraseology.
2. **Action:**
   - Reject listing with reason: `Prohibited Commercial Solicitation`.
   - Apply account strike: First strike = 7-day posting suspension; Second strike = Permanent ban.
   - System adds phone/email to watch list.

### 3.4 Scenario: Non-Consensual Imagery (NCII) / Doxxing
1. **Indicators:** Victim reporting their own photos uploaded without consent, phone numbers published with "prank" or derogatory captions, intimate photos accompanied by residential addresses.
2. **Action:**
   - Verify report claims.
   - Execute immediate permanent removal and purge image binaries from CDN cache.
   - Retain encrypted cold-storage copy in `evidence_preservations` for 180 days in case of civil litigation or subpoena.
   - Ban posting account and log hardware/IP footprint.

---

## 4. Moderation Actions & Technical Commands

Moderators utilize the Enterprise Admin Hub (`/admin/moderation` & `/admin/enterprise`):
- `Approve`: Sets listing status to `active`, publishes to search index, clears moderation item.
- `Quarantine`: Temporarily unpublishes listing from public view pending further investigation.
- `Reject`: Sets listing status to `rejected`, stores rejection reason code, sends email notice to user.
- `Suspend Account`: Suspends user access for designated duration (3 days, 7 days, 30 days, or permanent).
- `Shadowban Account`: Retains standard account look-and-feel for the user while silently hiding all listings from public discovery.

---

## 5. Auditor Logging & Quality Assurance

All moderator actions trigger immutable audit records via `AdminAuditService::log()`:
- `admin_id`: ID of the moderator.
- `action`: Approved, rejected, quarantined, banned, evidence frozen.
- `target_type`: Listing, User, DmcaRequest, LegalRequest.
- `target_id`: Database ID.
- `details`: Reason, risk score, applied notes.
- `ip_address`: Moderator workstation IP.

Weekly supervisor spot-checks review a random 10% sample of all approved and rejected listings to ensure uniform application of community guidelines.

