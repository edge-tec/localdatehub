# LocalAdZone — Monetization, Billing & Payment Processing Architecture

**Document ID:** ARCH-DOC-MON-001  
**Module:** `App\Modules\Billing`  
**Effective Date:** November 2026  
**Version:** 1.0.0  

---

## 1. Executive Summary & Business Model

LocalAdZone operates exclusively as a lawful classified advertising platform connecting consenting adults for personal, dating, and companionship connections. The platform provides transparent, configurable monetization without compromising community safety or regulatory compliance.

Monetization strictly observes:
1. **No Solicitation of Commercial Sexual Services:** Zero transaction processing or fee extraction for physical intimacy or prostitution (FOSTA-SESTA 18 U.S.C. § 2421A compliant).
2. **Provider Underwriting Transparency:** Acquirers and processors are engaged under full business disclosure. No deceptive Merchant Category Codes (MCC) or disguised processing.
3. **PCI-DSS Compliance:** Raw payment card account numbers (PAN) and CVV codes are never handled or stored on application servers.
4. **Defense-in-Depth Fraud Prevention:** Velocity thresholds, repeated card decline triggers, and transaction fingerprinting.

---

## 2. Product Catalog & Revenue Streams

The monetization engine supports six distinct revenue products:

| Product | Plan Type | Placement / Scope | Billing Cycle | Example Tier |
| :--- | :--- | :--- | :--- | :--- |
| **1. Free Community Listings** | `listing_upgrade` | Standard category & city search | 30 Days (free renewal) | Standard Member Listing ($0) |
| **2. Featured Listings** | `featured_spot` | Pinned with highlight border & badge | 7 Days | City Featured Highlight ($4.99) |
| **3. Premium Placement** | `featured_spot` | Multi-tier (`homepage`, `state_top`, `city_top`) | 14 Days | Metro Area Spotlight ($9.99) |
| **4. Listing Promotion Packages** | `bump` | Instant sorting bump to #1 rank | Immediate | Instant Bump to Top ($1.99) |
| **5. Recurring Memberships** | `membership` | Unlimited renewals, verified badges, free bumps | Monthly / Annual Recurring | LocalDateHub Verified Plus ($14.99/mo) |
| **6. Approved Display Ads** | `ad_placement` | Banner slots (`homepage_top`, `sidebar`, `footer`) | Fixed Campaign Window | Direct Sponsor Banners (CPM / Flat) |

---

## 3. Dynamic Pricing & Rules Engine

Dynamic pricing is managed through the `pricing_rules` registry and evaluated by `PromotionService::calculatePrice()`:

```
[Base Plan Price]
       │
       ▼
[PricingRule Match] ──► Checks (Plan ID, Category ID, State ID)
       │
       ├── is_free = true: Final Price = $0
       ├── fixed_price_cents != null: Final Price = Fixed Override
       └── discount_percentage > 0: Final Price = Base - (Base * %)
       │
       ▼
[Coupon Applied] ──► Fixed ($) or Percentage (%) subtraction
       │
       ▼
[Final Billed Amount]
```

### 3.1 Use Cases Handled:
- **Market Expansion / Free Launch:** Setting `is_free = true` allows free listings in targeted categories or newly launched states (e.g. initial launch in Texas or Florida).
- **High-Demand Category Surcharges / Discounts:** Targeted percentage discounts for underserved categories.
- **Promo Codes / Affiliates:** Time-limited coupon codes with usage caps (`coupons` table).

---

## 4. Payment Gateway Abstraction Layer

Mainstream card processors (standard Stripe, PayPal) restrict adult dating and classified platforms. To maintain modularity and regulatory compliance, LocalAdZone implements a robust driver-based gateway abstraction:

```
                       [PaymentGatewayManager]
                                  │
         ┌────────────────────────┼────────────────────────┐
         ▼                        ▼                        ▼
  [SandboxGateway]         [CcbillGateway]        [StripeAdultGateway]
  • Testing & CI           • High-risk MCC        • High-risk Underwritten
  • HMAC Webhooks          • Salt MD5 Hashes      • Exemption Verified
  • Deterministic sim      • FlexForms            • Live card tokens
```

### 4.1 Strict Adult Merchant Approval Gate
In `config/monetization.php`:
```php
'adult_merchant_approved' => (bool) env('PAYMENT_ADULT_MERCHANT_APPROVED', false),
```
If `adult_merchant_approved` is `false`, live production gateways (`CcbillGateway`, `StripeAdultGateway`) refuse transactions with a compliance safeguard error, preventing accidental deployment without written underwritten underwriting on file.

---

## 5. Invoicing & Financial Integrity

Every completed transaction generates an itemized electronic invoice stored in the `invoices` table:
- **Unique Sequence:** `INV-YYYYMM-XXXXX`
- **Platform Remit Disclosures:** Company legal name, registered Delaware address, support contact, and zero-tax notice.
- **Customer Information:** Masked billing address, name, email, and user account reference.
- **Line Items Breakdown:** Plan name, target listing title/ID, quantity, unit price, and total.
- **Access Controls:** Accessible by the account owner (`/account/invoices/{invoice}`) or administrative staff with `billing.view` permission; protected from unauthorized cross-tenant viewing (403 Forbidden).

---

## 6. Subscription Lifecycles & Renewal Operations

Recurring memberships (e.g., *LocalDateHub Verified Plus*) follow a defined lifecycle:

```
[Active] ──► [Renewal Date Reached] ──► Charge Gateway
                 │                            │
                 ├── Success: Extend ends_at + 30 days, generate invoice
                 └── Failed: Status -> [past_due], Grace Period = 3 days
                                 │
                                 ├── Retry Succeeds -> [Active]
                                 └── Grace Expires -> [Expired]
```

- **Graceful Cancellation:** When a user cancels (`cancelSubscription()`), `auto_renew` is set to `false`, but their membership benefits remain active until `ends_at`.
- **Scheduled Automated Renewals:** Executed via `php artisan monetization:process-renewals`.
- **Promotion Expiration Daemon:** Executed via `php artisan monetization:expire-promotions`.

---

## 7. Webhook Security & Idempotency

External webhooks (`/webhooks/payments/{gateway}`) are secured through three defensive layers:
1. **CSRF Bypass Exemption:** Registered in `bootstrap/app.php` (`validateCsrfTokens(except: ['webhooks/*', 'api/*'])`).
2. **Cryptographic Signature Verification:** Verifies provider HMAC-SHA256 or MD5 signatures (`verifyWebhookSignature()`). Invalid signatures immediately return `400 Bad Request`.
3. **Idempotency Protection:** Every inbound `event_id` is logged in `payment_webhooks`. Duplicate webhook transmissions return `200 OK` with `['status' => 'already_processed']`, guaranteeing that charges, promotions, and subscriptions are never applied more than once.

---

## 8. Multi-Vector Fraud & Velocity Monitoring

The `FraudMonitoringService` evaluates transactions prior to charging:
- **IP Velocity Threshold:** Flags or blocks if $> 5$ payment transactions originate from the same IP address within 60 minutes.
- **Account Declines Frequency:** Flags or blocks if $> 3$ consecutive declined cards occur within 60 minutes.
- **Unverified Age High-Value Filter:** Flags charges $> \$50.00$ initiated by unverified accounts.
- **Scoring & Tiers:** 
  - $0 - 34$: `low` (allowed)
  - $35 - 69$: `medium` (allowed with audit notation)
  - $70 - 89$: `high` (flagged for review)
  - $\ge 90$: `blocked` (declined immediately before contacting card network)
- **Zero Raw PAN Storage:** Only one-way cryptographic fingerprint hashes (`hash('sha256', $token . $appKey)`) are recorded in `payment_fraud_logs`.

---

## 9. Transaction Reconciliation

The `ReconciliationService` audits and synchronizes local records against processor batch settlements:
- Matches transactions by `gateway_transaction_id` or `reference_id`.
- Aligns local statuses (e.g., transition `pending` $\rightarrow$ `completed` or `refunded`).
- Sets `is_reconciled = true` and `reconciled_at = now()`.
- Unreconciled transactions older than 2 hours are surfaced in the Enterprise Admin Hub.
- Console command available: `php artisan monetization:reconcile`.
