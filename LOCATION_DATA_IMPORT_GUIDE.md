# LocalAdZone / LocalDateHub — USA Geographic Location Data Import Guide

## 1. Executive Summary & Purpose

This guide provides exhaustive procedures for importing, validating, maintaining, and auditing official United States geographic location datasets for **LocalAdZone / LocalDateHub**.

### Geographic Scope
- **All 50 US States** (Alabama through Wyoming).
- **Washington, District of Columbia (DC)**.
- **Configurable US Territories**:
  - Puerto Rico (`PR`, FIPS: `72`)
  - Guam (`GU`, FIPS: `66`)
  - U.S. Virgin Islands (`VI`, FIPS: `78`)
  - American Samoa (`AS`, FIPS: `60`)
  - Northern Mariana Islands (`MP`, FIPS: `69`)
- **Counties / Parishes / Boroughs** mapped to states with official FIPS codes.
- **Incorporated Cities & Populated Places** with WGS84 coordinates, population metrics, and timezone data.
- **Postal ZIP Codes** with multi-locality mapping support.
- **Location Aliases** (Metropolitan areas, colloquial names, neighborhoods).

---

## 2. Authoritative Data Sources & Licensing

| Geographic Entity | Source Organization & Dataset | Identifier Standard | License / Rights |
| :--- | :--- | :--- | :--- |
| **States & Territories** | U.S. Census Bureau Tiger/Line 2024 | ANSI INCITS 38:2009 / FIPS 2-digit | Public Domain (U.S. Federal Work) |
| **Counties** | U.S. Census Bureau Geography Division | FIPS 5-digit (State + County) | Public Domain |
| **Cities & Populated Places** | U.S. Geological Survey (USGS) GNIS & Census Gazetteer | GNIS Feature ID / ANSI Code | Public Domain |
| **ZIP Codes (ZCTA)** | U.S. Census Bureau ZCTA & USPS AIS | 5-Digit Postal Code / Centroid | Public Domain / Open Postal Data |
| **Metropolitan Aliases** | Curated LocalAdZone Ontology & GeoNames | Custom Slug Mappings | Creative Commons Attribution 4.0 (CC-BY) |

---

## 3. Database Schema & Relational Integrity

The location engine spans 6 core tables:

```
countries
   └── states (with is_territory flag)
          ├── counties
          └── cities (with spatial latitude, longitude, population, timezone)
                 ├── zip_codes (1-to-many relationship supporting postal overlaps)
                 ├── location_aliases (metro areas, colloquial names, neighborhoods)
                 └── listings (personal classified advertisements)
```

### Key Semantics:
- **Non-Exclusive ZIP Mapping:** A single postal ZIP code may legitimately span multiple municipal boundaries. The `zip_codes` table maintains indexes on `['city_id', 'zip_code']` and `['state_id', 'zip_code']` rather than a naive unique constraint on `zip_code` alone.
- **Privacy Jitter:** All public listing coordinate displays apply 300m–800m random bearing displacement to preserve user physical privacy.

---

## 4. Import & Validation Commands

### 4.1 Running the Direct Dataset Import
```bash
# Import all bundled geographic datasets synchronously
php artisan locations:import

# Import only specific tiers
php artisan locations:import --only=states
php artisan locations:import --only=counties
php artisan locations:import --only=cities
php artisan locations:import --only=zips
php artisan locations:import --only=aliases

# Specify custom source directory
php artisan locations:import --path=database/data/locations/
```

### 4.2 Running Asynchronous Background Import via Horizon / Queue
```bash
# Dispatch import task to background queue worker
php artisan locations:import --queue
```

### 4.3 Running Exhaustive Data Integrity Validation
```bash
# Verify coordinate boundaries, referential integrity, and postal formats
php artisan locations:validate
```

Output:
```
+-----------------------------------------------+--------+-----------------------------------------------------------------------+
| Integrity Check Component                     | Status | Diagnostic Details                                                    |
+-----------------------------------------------+--------+-----------------------------------------------------------------------+
| US States & DC Representation                 | PASS   | 56 active entities verified                                           |
| City Coordinate Boundaries                    | PASS   | 100% of city coordinates within [-90, 90] & [-180, 180]                |
| ZIP Coordinate Boundaries                     | PASS   | 100% of ZIP centroids within valid boundaries                         |
| Postal Code Format (5-digit numeric)          | PASS   | All 48 ZIP codes match ^\d{5}$ pattern                                |
| Referential Integrity (Cities -> States)      | PASS   | Zero orphaned cities                                                  |
| Referential Integrity (ZIPs -> Cities/States) | PASS   | Zero orphaned ZIP codes                                               |
| Multi-Locality ZIP Relationships              | INFO   | 1 ZIP codes successfully mapping across multiple municipal localities |
| Referential Integrity (Aliases -> Cities)     | PASS   | 27 aliases active and linked                                          |
+-----------------------------------------------+--------+-----------------------------------------------------------------------+
✓ Location Engine Dataset Integrity Audit Passed 100%!
```

---

## 5. Audit Logging & Import Version History

Every execution of `php artisan locations:import` records an audit entry in the `location_import_histories` database table:
- Dataset name and version tag (`2026.1`)
- SHA-256 checksum of source files
- Exact duration in milliseconds
- Total records processed, created, and updated
- Final execution status (`completed` / `failed`)

Administrators can inspect the full historical audit log by navigating to:
```
GET /admin/locations/history
```
Or trigger an immediate dataset refresh directly from the administrative UI.

---

## 6. Updating Datasets from Future Census Releases

1. **Download:** Obtain official Gazetteer files from [Census Bureau Tiger/Line Gazetteer](https://www.census.gov/geographies/reference-files/time-series/geo/gazetteer-files.html).
2. **Convert:** Format places into `database/data/locations/us_cities.json` and ZIPs into `us_zip_codes.json`.
3. **Audit:** Execute `php artisan locations:validate` in local/staging environment.
4. **Deploy:** Deploy updated JSON files and execute `php artisan locations:import`.
