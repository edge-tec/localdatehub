# Modular Monolith Architecture: LocalDateHub

**Author:** Principal Software Architect  
**Version:** 1.0.0  
**Phase:** Phase 01 Implementation

---

## 1. Architectural Philosophy

LocalDateHub is architected as a **Domain-Oriented Modular Monolith**. Instead of scattering business logic across unstructured controllers or splitting early into fragile microservices, the application is partitioned into eleven self-contained modules located in `app/Modules/`.

Each module manages its own DTOs, Services, Repositories, Events, Actions, and Policies, while sharing global infrastructure support via `app/Support/`.

---

## 2. Directory Layout & Module Responsibilities

```text
app/
├── Modules/
│   ├── Accounts/        # Registration, authentication, profile lifecycle, age assurance
│   ├── Locations/       # Spatial GIS coordinates, anti-triangulation fuzzing, distance bucketing
│   ├── Categories/      # Adult personal classifications (Men Seeking Women, LGBTQ+, etc.)
│   ├── Listings/        # Personal classifieds creation, keyword moderation, media attachment
│   ├── Search/          # Radius filtering, keyword queries, age & preference filters
│   ├── Contact/         # Encrypted 1-on-1 messaging, scam & financial solicitation prevention
│   ├── Moderation/      # Trust & safety reports, review queues, user blocking, automated flags
│   ├── Billing/         # Membership plans, featured ad boosts, subscription tokens
│   ├── Admin/           # Platform metrics, health telemetry, administrative controls
│   ├── SEO/             # Dynamic city & category meta tags, OpenGraph, search indexing
│   └── Notifications/   # Multi-channel notification dispatching (Database, Push, Email)
└── Support/
    ├── Exceptions/      # Domain-level exceptions (AgeVerificationException, ProhibitedContentException)
    ├── Health/          # Liveness and readiness diagnostic service
    ├── Responses/       # Standardized ApiResponse JSON contract
    └── Storage/         # Local and S3-compatible media storage with EXIF sanitization
```

---

## 3. Module Breakdown & Key Contracts

### 3.1 Accounts Module (`App\Modules\Accounts`)
- **Key Classes:** `RegisterAccountData` (DTO), `AccountService`.
- **Safety Enforcement:** Enforces strict 18+ birthdate verification before account creation. Any birthdate under the threshold raises an `AgeVerificationException`.

### 3.2 Locations Module (`App\Modules\Locations`)
- **Key Classes:** `CoordinateDTO`, `LocationService`.
- **Anti-Triangulation Engine:** Implements `fuzzCoordinates` which applies a random 300m–800m offset jitter to coordinates. Exact user coordinates are never returned to client consumers.
- **Distance Bucketing:** Displays coarse bands (`"Less than 1 km away"`, `"~5 km away"`, `"Nearby"`).

### 3.3 Categories Module (`App\Modules\Categories`)
- **Key Classes:** `CategoryDTO`, `CategoryService`.
- **Curated Taxonomies:** Maintains database-driven and compliant adult dating categories (Men Seeking Women, Women Seeking Men, LGBTQ+ Connections, Activity Companions, Casual Meets, Serious Relationships).

### 3.4 Listings Module (`App\Modules\Listings`)
- **Key Classes:** `CreateListingData` (DTO), `ListingService`.
- **Content Moderation Filter:** Evaluates listing titles and descriptions against prohibited regex patterns (commercial sex work, escorts, pay-per-meet, sugar babying, trafficking terms). Violations trigger `ProhibitedContentException`.

### 3.5 Contact Module (`App\Modules\Contact`)
- **Key Classes:** `ContactMessageData` (DTO), `ContactService`.
- **Fraud Prevention:** Scans messaging payloads for financial scam markers (wire transfer, cash app, gift cards, crypto solicitation).

### 3.6 Moderation Module (`App\Modules\Moderation`)
- **Key Classes:** `ReportData` (DTO), `ModerationService`.
- **Report Queue:** Handles user reports across 8 standardized violation categories (`commercial_solicitation`, `harassment`, `underage_suspected`, `fake_impersonation`, etc.).

### 3.7 Storage & Media Pipeline (`App\Support\Storage\MediaStorageService`)
- Re-encodes uploaded JPEG/PNG/WebP images using GD.
- Completely removes all EXIF metadata (camera serial numbers, GPS tags, timestamps) before saving to local or S3-compatible disks.
- Stores optimized `.webp` bitmaps to save bandwidth and ensure user privacy.

---

## 4. Standardized API Response Contract

All JSON responses follow a uniform contract via `App\Support\Responses\ApiResponse`:

```json
{
  "success": true,
  "status": 200,
  "message": "Human readable summary",
  "data": { ... },
  "meta": { ... }
}
```

On error:
```json
{
  "success": false,
  "status": 422,
  "message": "The submission contains prohibited commercial terms.",
  "errors": { ... },
  "error_code": "PROHIBITED_CONTENT_DETECTED"
}
```
