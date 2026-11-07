# LocalAdZone — Trust & Safety Policy

**Effective Date:** January 1, 2026  
**Last Updated:** November 8, 2026  
**Document Version:** 1.0.0  

---

## 1. Core Commitment & Platform Philosophy

LocalAdZone operates exclusively as a lawful classified advertising platform connecting consenting adults (aged 18 and older, or 21 and older where mandated by state statutes) for personal, dating, social, and non-commercial companionship connections across the United States.

LocalAdZone strictly adheres to federal and state laws and maintains a **Zero-Tolerance Policy** against:
1. **Commercial Sexual Services / Prostitution:** Solicitations, offers, prices, or negotiations for commercial sexual acts, prostitution, or escort services of a transactional nature (in full compliance with 18 U.S.C. §§ 1591, 2421, 2421A FOSTA-SESTA).
2. **Child Sexual Abuse Material (CSAM) & Underage Access:** Any involvement, portrayal, reference, or participation by any individual under the age of majority. LocalAdZone cooperates fully with the National Center for Missing & Exploited Children (NCMEC) and federal law enforcement.
3. **Human Trafficking & Involuntary Servitude:** Any form of coercive, forced, debt-bonded, or non-consensual exploitation.
4. **Non-Consensual Imagery (NCII) / "Revenge Porn":** Any media published without explicit, verifiable consent of every depicted participant.
5. **Violence, Hate Speech, & Harassment:** Stalking, extortion, doxxing, impersonation, or physical intimidation.
6. **Illicit Substances & Controlled Items:** Facilitation or advertisement of narcotics, prescription pharmaceuticals, firearms, or contraband.

---

## 2. Statutory Legal Framework & Compliance Regimes

LocalAdZone is engineered to operate under strict compliance with United States statutory frameworks:

| Statute / Regulation | Compliance Mandate | LocalAdZone Enforcement Mechanism |
| :--- | :--- | :--- |
| **FOSTA-SESTA** (18 U.S.C. § 2421A) | Prohibition of intentionally promoting or facilitating prostitution or trafficking. | Automated text screening (`TextScreeningService`), immediate quarantine of suspicious keywords, zero financial transaction support for adult services. |
| **18 U.S.C. § 2257 & § 2257A** | Record-keeping obligations for sexually explicit depictions. | Policy restrictions on explicit depiction; strict photo moderation and mandatory record preservation for compliance audits. |
| **18 U.S.C. § 2703(f)** | Stored Communications Act preservation requests from law enforcement. | `ComplianceService::freezeEvidenceForLegalRequest()`, automated evidence vault snapshots, administrative hold preventing record deletion. |
| **Digital Millennium Copyright Act (DMCA)** (17 U.S.C. § 512) | Safe harbor takedown notices and counter-notices. | Dedicated public filing form (`/dmca`), unique tracking codes, automated listing unpublishing, counter-notice workflow, 14-day restitution windows. |
| **State Statutory Age Verification Acts** | Age verification before granting access to adult material (TX HB 1181, LA Act 440, UT SB 287, VA SB 1515, etc.). | `StateCompliance` registry, geo-blocking and strict age-assurance gates preventing unverified viewers from browsing adult categories in restricted states. |
| **CCPA / CPRA & State Privacy Acts** | Consumer rights to data access and erasure. | Self-service data export and deletion tools with strict legal-hold safety checks preventing spoliation of subpoenaed records. |

---

## 3. Prohibited Content and Unacceptable Behaviors

### 3.1 Absolute Prohibitions (Immediate Permanent Ban & Referral)
- Any portrayal, mention, or implication of minors or school-age individuals.
- Solicitations quoting monetary figures, donations, fees, gifts, roses, or hourly compensation in exchange for physical intimacy.
- Non-consensual sexual content, surreptitiously recorded media, or unverified leaks.
- Blackmail, extortion, threats of exposure ("outing"), or doxxing of private personal information.
- Sale or trade of illicit controlled substances, prescription drugs, or weapons.

### 3.2 Moderate / Contextual Violations (Quarantine & Review)
- Ambiguous compensation language, code words, or emojis commonly used to disguise commercial transactions.
- Obfuscated phone numbers, links to unauthorized off-platform payment portals (CashApp, Venmo, PayPal, cryptocurrency).
- Duplicate spam campaigns or cross-state automated mass posting.
- Inappropriate photos containing explicit genitalia where thumbnail preview blur rules are violated.

---

## 4. Reporting Mechanisms & Expedited Takedown

### 4.1 Community Reporting
- **Listing Reports:** Every listing displays a clear "Report Listing" action with granular categories:
  - `underage_suspected`
  - `trafficking_coercion`
  - `non_consensual_media`
  - `commercial_sex_solicitation`
  - `harassment_or_doxxing`
  - `scam_fraud`
  - `duplicate_spam`
- **User Reports:** Public user profiles support direct user reporting (`POST /users/{user}/report`).

### 4.2 Automated Fast-Track Takedowns
When a report is filed citing `underage_suspected`, `non_consensual_media`, or `trafficking_coercion`:
1. The reporting system sets `is_urgent_takedown = true` and `risk_level = critical`.
2. The listing status is immediately transitioned to `quarantined`, unpublishing it from all public search and index pages without waiting for moderator triage.
3. An urgent entry is created in the Enterprise Moderation Queue with an urgency score of 100.
4. An automated evidence snapshot is preserved to prevent spoliation.

---

## 5. Account Sanctions, Suspension, & Appeals

1. **Warning:** Notification delivered to user inbox for minor formatting violations.
2. **Temporary Suspension:** Account restricted from posting, messaging, or editing for 3 to 30 days.
3. **Permanent Ban:** Account revoked, phone number and email blacklisted, IP hash recorded for prevention.
4. **Shadowban:** Profile and listings suppressed from public indices and feeds without alerting bad actors, reducing evasion attempts.
5. **Appeals Workflow:** Users receiving disciplinary action may submit a formal appeal through `/account/appeal`. Appeals are independently evaluated by Level 2 Trust & Safety leads within 72 business hours.

---

## 6. Transparency and Law Enforcement Cooperation

LocalAdZone maintains a designated Legal Response Unit for valid legal process (subpoenas, court orders, warrants, and 18 U.S.C. 2703(f) preservation requests). Inquiries may be directed through the administrative legal portal or verified government email addresses.
