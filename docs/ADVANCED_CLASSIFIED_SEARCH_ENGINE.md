# LocalAdZone / LocalDateHub — Advanced Classified Search Engine

## 1. Executive Summary

**Phase 08: Advanced Classified Search Engine** delivers a production-grade, multi-parameter faceted search and discovery engine for adult personal classified advertisements. Built on an enterprise modular monolith pattern, the architecture provides dual-driver search: a high-performance **Meilisearch** driver where configured, and an industrial-strength, spatial-aware **Database** driver fallback that functions identically with zero external dependencies.

---

## 2. Architecture & Design Principles

```
+-------------------------------------------------------------+
|                  Client / Web & API Interface                |
|           /search (Web)   &   /api/search/autocomplete      |
+-------------------------------------------------------------+
                               |
                               v
+-------------------------------------------------------------+
|            SearchFilterRequest & SearchFilterDTO            |
|       Validates all query parameters & sanitizes input       |
+-------------------------------------------------------------+
                               |
                               v
+-------------------------------------------------------------+
|                        SearchService                         |
|   Orchestrator, Driver Health Check, Category Facet Counts   |
+-------------------------------------------------------------+
              |                                 |
              v (if available)                  v (transparent fallback)
+---------------------------+     +---------------------------+
|    MeilisearchDriver      |     |   DatabaseSearchDriver    |
| - Fast document lookup    |     | - Multi-table Eloquent    |
| - Safe sanitized fields   |     | - Spatial Bounding Boxes  |
| - Zero private user info  |     | - Dynamic Attributes EAV  |
| - Catches exceptions      |     | - Transparent Ranking     |
+---------------------------+     +---------------------------+
              |                                 |
              +----------------+----------------+
                               |
                               v
+-------------------------------------------------------------+
|                   LengthAwarePaginator                      |
|         Hydrated Active Listings with Eager Loading         |
+-------------------------------------------------------------+
```

### 2.1 Pluggable Search Contract
- **Interface:** `App\Modules\Search\Contracts\SearchDriverInterface`
- **Implementations:**
  - `DatabaseSearchDriver`: Eloquent + MySQL/SQLite spatial bounding box queries.
  - `MeilisearchDriver`: HTTP REST integration with automatic connection timeout protection (2.0s max timeout), index synchronization, and silent fallback on unavailability.

### 2.2 Privacy & Security Safeguards (Zero Leakage)
- **Zero Private User Data in Indexes:** Search documents strictly exclude passwords, email addresses, phone numbers, identity documents, and real residential GPS coordinates.
- **Location Anti-Triangulation:** Only fuzzed coordinates (jittered by 300–800m) are stored or compared.
- **Strict Exclusion of Non-Active Records:** `draft`, `pending_review`, `expired`, `rejected`, `suspended`, and `deleted` listings are categorically filtered out at the query level and removed from indexes.

---

## 3. Search Criteria & Filter Matrix

| Criterion | Parameter | Implementation Details |
| :--- | :--- | :--- |
| **Category** | `category` | Resolves parent category and traverses all descendants via `descendantIds(true)`. |
| **Subcategory** | `subcategory` | Direct filter targeting specific child category slug. |
| **State** | `state` | 2-letter uppercase US state code (e.g. `TX`, `CA`, `FL`). |
| **City** | `city` | Partial or exact name match against indexed `cities` relation. |
| **ZIP Code** | `zip` | 5-digit US postal code. Geocodes via `ZipCode` model to obtain latitude/longitude and bounds results within radius. |
| **Distance Radius** | `radius` | Integer in kilometers (10, 25, 50, 100, 150 km) applying bounding box calculation around resolved coordinates. |
| **Dynamic Attributes** | `attr[...]` | EAV queries against `listing_attributes` (e.g. `attr[intent]=dating`, `attr[intent]=activity_partner`). |
| **Published Date** | `published_date` | Time-window bounding: `today` (past 24h), `7d` (past 7 days), `30d` (past 30 days), or `all`. |
| **Featured Status** | `featured_only` | Boolean filter restricting results strictly to active featured listings (`is_featured = true`). |
| **Photo Availability** | `with_photos` | Boolean filter (`has_photos = true`). |
| **Verified Status** | `verified_only` | Boolean filter ensuring poster has completed age/identity verification (`is_verified_poster = true`). |
| **Poster Age Range** | `min_age`, `max_age` | Numeric range bounding (`poster_age BETWEEN min AND max`, strictly >= 18). |
| **Keyword Search** | `q` | Substring match against `title` and sanitized `description`. |

---

## 4. Transparent Ranking Rules

The platform follows a clear and auditable ranking hierarchy:

1. **Rule 1 — Priority Featured Placement:**
   Listings with active City Featured or Metro Spotlight status (`is_featured = true`) appear at the top of results.
2. **Rule 2 — Secondary Sort Order:**
   Within featured and organic tiers, listings are strictly ordered by the user's chosen sort parameter:
   - `newest` (Default): `published_at DESC, id DESC`
   - `updated`: `COALESCE(renewed_at, updated_at) DESC, id DESC`
   - `views`: `view_count DESC, id DESC`
   - `oldest`: `published_at ASC, id ASC`

---

## 5. Asynchronous Search Indexing

- **Sync Job:** `App\Modules\Search\Jobs\SyncListingToSearchIndexJob`
  - Queued on `ListingSearchObserver` when an active listing is created, approved, renewed, or updated.
  - Automatically verifies active status before pushing sanitized documents to Meilisearch.
- **Removal Job:** `App\Modules\Search\Jobs\RemoveListingFromSearchIndexJob`
  - Queued when a listing is paused, rejected, suspended, expired, or deleted.
- **Resilience:** Indexing failures catch exceptions and log warnings without failing parent user transactions or blocking job workers.

---

## 6. Autocomplete API Endpoint

- **Route:** `GET /api/search/autocomplete?q={term}`
- **Controller:** `App\Http\Controllers\Api\SearchApiController@autocomplete`
- **Output:**
  - `cities`: Top matching US cities with state code and coordinates.
  - `zips`: Matching 5-digit ZIP codes with associated city and state.
  - `categories`: Active categories and subcategories matching the search term.

---

## 7. Verification & Automated Test Suite

- **Test Suite:** `tests/Feature/AdvancedClassifiedSearchEngineTest.php` (12 tests, 54 assertions)
- **Full Application Suite:** 103 tests passing, 592 assertions, 0 errors, 0 regressions.
- **Frontend Assets:** Clean Vite 8 production build (`npm run build`).

