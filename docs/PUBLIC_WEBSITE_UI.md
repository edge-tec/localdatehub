# LocalDateHub — Public Website UI & Search Architecture

## 1. Overview

**Phase 07: Public Website UI** delivers a modern, accessible (WCAG 2.2 AA compliant), mobile-first public web portal for LocalDateHub. The platform replaces static mockups with live, database-driven classified listings, interactive search and filtering, comprehensive trust & safety documentation, and encrypted support communication.

---

## 2. Component Inventory

The UI design system is built with modular Blade components located in `resources/views/components/`:

| Component | File | Description & Capabilities |
| :--- | :--- | :--- |
| **Listing Card** | `components/listing-card.blade.php` | High-contrast classified card displaying thumbnail with fallback, status badges (Featured, Verified Poster, Distance), title, age/gender, fuzzed location, view stats, and hover elevation. |
| **Search Filter** | `resources/views/search/index.blade.php` | Multi-parameter sidebar with keyword search, category hierarchy selection, US state & city selectors, distance radius, age range (min/max), photo and verification toggles. |
| **Modal** | `components/modal.blade.php` | Alpine.js dialog modal with keyboard escape trap, backdrop click, focus management, and accessibility ARIA roles. |
| **Breadcrumbs** | `components/breadcrumbs.blade.php` | Accessible chevron breadcrumb navigation linking hierarchical pages. |
| **Input** | `components/input.blade.php` | Form input with integrated label, error message presentation, hint text, and auto-focus styling. |
| **Select** | `components/select.blade.php` | Form select supporting key-value options arrays, default selections, and validation states. |
| **Empty State** | `components/empty-state.blade.php` | Polished empty state with icon, descriptive messaging, and optional call-to-action button. |
| **Loading State** | `components/loading.blade.php` | SVG spinner loading indicator supporting small, medium, and large sizing tokens. |
| **Navigation** | `components/navbar.blade.php` | Sticky responsive navbar with brand identity, search button, category/location links, Trust & Safety indicator, user state, and mobile hamburger slide-out menu. |
| **Footer** | `components/footer.blade.php` | Four-column responsive footer connecting discovery pages, trust/legal links, company info, and real-time system health probe. |

---

## 3. Public Pages Architecture

All static and content pages are served by `app/Http/Controllers/PublicPageController.php`:

### 3.1 About Page (`/about` — `pages.about`)
- Articulates LocalDateHub’s mission as an authentic, non-gamified adult classifieds platform.
- Details the privacy-first architecture: zero EXIF tracking, automated location fuzzing (300–800m jitter), and strict 18+ enforcement.

### 3.2 Trust & Safety Center (`/safety` — `pages.safety`)
- Prominent emergency hotlines banner (911, National Human Trafficking Hotline 1-888-373-7888).
- Federal compliance documentation (FOSTA-SESTA, 18 U.S.C. §§ 1591, 2421A).
- Zero tolerance protocols for commercial sex work, prostitution, underage access, and non-consensual imagery.
- In-person meeting guidelines (public venues, emergency contacts, zero money transfers).

### 3.3 Contact & Support (`/contact` & `POST /contact` — `pages.contact`)
- Supports public support inquiries with field-level AES-256 encryption (`ContactMessage`).
- Categorized inquiry topics: General Support, Safety Report (1-hour response SLA), Billing, and Partnerships.
- Rate-limited to 10 requests per minute with CSRF validation.

### 3.4 Privacy Policy (`/privacy` — `pages.privacy`)
- Comprehensive privacy policy addressing CCPA, CPRA, and GDPR standards.
- Detailed explanation of anti-triangulation location security and automated EXIF stripping.
- Affirmation of zero third-party data brokerage or ad network tracking.

### 3.5 Terms of Service (`/terms` — `pages.terms`)
- Binding 18+ user agreement and penalty of perjury declaration.
- Permitted vs. prohibited conduct.
- Rights to moderation, content removal, and immediate criminal reporting to NCMEC.

### 3.6 Pricing & Upgrades (`/pricing` — `pages.pricing`)
- Dynamically populated from `PricingPlan::where('is_active', true)->get()`.
- Highlights Free Standard Listing ($0 / 30 days), City Featured Highlight ($4.99 / 7 days), and Metro Area Spotlight ($9.99 / 14 days).
- Transparent pricing guarantee with no recurring surprise subscriptions.

---

## 4. Search & Discovery Engine

### 4.1 Search Service (`app/Modules/Search/Services/SearchService.php`)
- `searchListings(SearchFilterDTO $filter, int $perPage = 15)`:
  - Text search on `title` and `description`.
  - Category hierarchy matching via `descendantIds(true)`.
  - Geographic city and state filtering.
  - Coordinate radius filtering against fuzzed coordinates with bounding box optimization.
  - Boolean filters for `has_photos` and `is_verified_poster`.
  - Age range bounding (`poster_age BETWEEN min AND max`).
  - Priority sorting: Featured listings automatically pinned first, followed by publication date or view metrics.

### 4.2 Web Controller (`app/Http/Controllers/SearchWebController.php`)
- Renders `resources/views/search/index.blade.php`.
- Binds `SearchFilterDTO` from URL query parameters.
- Provides accessible filter sidebar with instant reset links and active result counters.

---

## 5. Homepage Integration (`welcome.blade.php` & `HomeController.php`)

- **Dynamic Hero Search Bar:** Directs users to `/search` with selected category, city, and distance radius.
- **Category Grid:** Displays top active parent categories with direct links to `/categories/{slug}`.
- **Featured Personals Section:** Dynamically showcases active listings where `is_featured = true`.
- **Latest Classifieds Stream:** Renders the newest active published listings.
- **Popular Cities Directory:** Pulls `City::where('is_popular', true)` with direct state/city routes.
- **Platform Telemetry:** Live counter of active listings, supported US states, and age-verified members.
- **Livewire Diagnostic Status:** Reactive component reporting API latency and queue health.

---

## 6. Custom Error Handling

Styled error templates matching platform dark design tokens:
- **404 Page Not Found** (`resources/views/errors/404.blade.php`): Friendly recovery actions with return to homepage and search triggers.
- **403 Access Forbidden** (`resources/views/errors/403.blade.php`): Explains authorization boundaries with profile access links.
- **500 Internal Server Error** (`resources/views/errors/500.blade.php`): Professional notification with direct link to Support.

---

## 7. Testing & Quality Assurance

Comprehensive automated tests in `tests/Feature/PublicWebsiteUiTest.php` (12 test cases):
- `test_homepage_renders_successfully_with_database_data`
- `test_search_page_renders_with_filter_controls`
- `test_search_filters_by_keyword`
- `test_search_filters_by_category`
- `test_search_filters_by_city_and_state`
- `test_search_filters_by_photos_only_and_age_range`
- `test_public_static_pages_return_successful_responses`
- `test_pricing_page_displays_active_plans`
- `test_contact_form_submits_and_stores_encrypted_message`
- `test_contact_form_validates_required_fields`
- `test_404_error_page_renders_for_missing_listing`
- `test_navigation_and_footer_contain_all_critical_links`

**Entire Suite Status:** 91/91 tests passing, 538 assertions, 0 failures.
