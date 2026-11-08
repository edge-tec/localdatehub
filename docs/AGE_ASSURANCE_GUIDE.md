# LocalAdZone — Age Assurance & Statutory State Verification Guide

**Document ID:** SEC-DOC-AGE-001  
**Scope:** Age verification protocols, state-specific legal statutes, and 18 U.S.C. § 2257 record-keeping.  
**Effective Date:** November 2026  

---

## 1. Legal Mandate Overview

Multiple U.S. state legislatures have enacted mandatory digital age verification statutes requiring commercial entities distributing material deemed harmful or adult-oriented to verify that visitors are at least 18 years of age prior to granting access to such material.

Furthermore, 18 U.S.C. §§ 2257 and 2257A require strict age verification and record-keeping for producers and hosts of depictions of actual or simulated sexually explicit conduct.

---

## 2. State-by-State Statutory Landscape

LocalAdZone maintains a dynamic state compliance engine via the `state_compliances` registry and `ComplianceService::checkStateCompliance()`:

| State | Statutory Bill | Age Minimum | Approved Verification Methods | LocalAdZone Enforcement |
| :--- | :--- | :--- | :--- | :--- |
| **Texas (TX)** | HB 1181 | 18 | Commercial age-verification service, government-issued digital ID. | Block browsing of adult categories without verified status. Warning interstitial displayed. |
| **Louisiana (LA)** | Act 440 | 18 | Digitized identification card (LA Wallet) or transactional verification. | Strict gate: requires verified KYC badge on user account. |
| **Utah (UT)** | SB 287 | 18 | Digitized ID card, commercially reasonable age verification. | Access denied to unverified users for adult categories. |
| **Virginia (VA)** | SB 1515 | 18 | Commercially reasonable age-verification method. | Interstitial age-assurance verification gate. |
| **Florida (FL)** | HB 3 | 18 (14-15 w/ consent) | Anonymous age-verification third-party software. | Mandatory age-verification gate for adult listings. |
| **California (CA)** | AB 2273 (AADC) | 18 | Age estimation, age assurance, or parental consent where applicable. | Privacy-preserving age assurance and default high-privacy settings. |

---

## 3. LocalAdZone Age Assurance Implementation Architecture

### 3.1 Verification Methods Supported

```
                      [User Session]
                            │
               ┌────────────┴────────────┐
               ▼                         ▼
      [Self-Declaration]       [Statutory Jurisdictions]
      (Default States)         (TX, LA, UT, VA, etc.)
               │                         │
      • 18+ Click-Through       • Third-Party Identity Verification
      • Cookie Acceptance       • Mobile Driver's License (mDL)
                                • Zero-Knowledge Proof Age Token
                                         │
                                         ▼
                            [User `is_age_verified` = true]
                            [Timestamp & State Compliance Log]
```

1. **Self-Declaration (Non-Restricted States):**
   - Full-page legal disclaimer requiring explicit affirmative confirmation of 18+ age before initial site entry or category access.
   - Encrypted session cookie stores confirmation.

2. **Commercial Identity Verification (Statutory Mandate States):**
   - Direct integration via secure webhooks with certified age verification providers (e.g., Yoti, Veriff, Persona, LA Wallet API).
   - LocalAdZone receives an anonymous cryptographically-signed token confirming: `age >= 18: true`.
   - LocalAdZone does **NOT** store raw government ID images, driver's license numbers, or Social Security numbers on its primary application database, preventing data breach liability.

3. **18 U.S.C. 2257 Record Preservation:**
   - Where depiction verification is required, verified record references, custodian names, and record locations are indexed in `evidence_preservations` with retention schedules conforming to federal requirements.

---

## 4. Technical Engine: `ComplianceService`

In `app/Modules/Moderation/Services/ComplianceService.php`:

```php
public function checkStateCompliance(string $stateCode, ?User $user): array
{
    $compliance = StateCompliance::where('state_code', strtoupper($stateCode))->first();

    if (! $compliance || ! $compliance->is_active) {
        return ['allowed' => true, 'requires_id' => false];
    }

    if ($compliance->requires_age_verification) {
        $isVerified = $user && $user->is_age_verified;
        return [
            'allowed' => $isVerified,
            'requires_id' => true,
            'statute_reference' => $compliance->statute_reference,
            'warning_message' => $compliance->warning_message,
        ];
    }

    return ['allowed' => true, 'requires_id' => false];
}
```

---

## 5. Privacy Protections in Age Verification

To prevent user tracking and comply with state biometric privacy laws (e.g., Illinois BIPA, Texas CUBI):
1. **Zero Raw Storage:** No photos of driver's licenses, passports, or facial biometric scans are stored on LocalAdZone servers.
2. **Ephemeral Verification:** Verification status is maintained as a binary flag `is_age_verified` with a verification date and unique non-reversible transaction hash.
3. **Data Segregation:** Identity audit logs are kept in cold-storage audit vaults with restricted Level 3 access only.

