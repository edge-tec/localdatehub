# LOCALADZONE — SEO, ANALYTICS & NOTIFICATIONS ENGINE

## Architecture & Implementation Overview (Phase 14)

LocalAdZone includes a production-grade Search Engine Optimization (SEO), privacy-first Analytics, and multi-channel Notifications engine engineered specifically for high-volume classified advertisements.

---

## 1. Dynamic Meta & Schema.org JSON-LD (`SeoService`)

The `SeoService` (`app/Modules/SEO/Services/SeoService.php`) computes canonical tags, open graph metadata, crawler directives (`robots`), and rich structured JSON-LD data for all platform pages:

- **Home Page:**
  - OpenGraph `website` metadata.
  - Schema.org `WebSite` definition with Google Sitelinks Searchbox integration pointing to `/search?q={search_term_string}`.
- **Listing Detail Pages:**
  - Canonical URL pointing strictly to the primary slug route.
  - Robots: `index, follow` for active listings; `noindex, follow` for expired/rejected listings.
  - Schema.org `ItemPage` and `BreadcrumbList` structured markup with pricing, datePublished, location specifications, and safe content descriptors.
- **Category & Location Landing Pages:**
  - Canonical URLs for base hierarchy (e.g. `/categories/{slug}`, `/locations/{stateCode}`, `/locations/{stateCode}/{citySlug}`).
  - Dynamic H1 and title generators (e.g. `Women Seeking Men Classifieds in Los Angeles, CA - LocalAdZone`).
  - Schema.org `CollectionPage` structured data.
  - **Pagination SEO Anti-Bloat:** Page 1 defaults to `index, follow`. Page 2+ dynamically sets `noindex, follow` and appends `Page X` to the meta title to prevent indexing duplicate pagination sequences.

---

## 2. Dynamic XML Sitemaps & Robots.txt

Sitemaps are managed by `app/Http/Controllers/SEO/SitemapController.php`:

| Endpoint | Description | Cache Cadence |
|---|---|---|
| `/sitemap.xml` | Master Sitemap Index referencing all sub-sitemaps | 3,600s (1 hr) |
| `/sitemaps/pages.xml` | Static platform pages (About, Safety, Terms, Privacy, Pricing) | 86,400s (24 hr) |
| `/sitemaps/categories.xml` | Active root and child categories | 3,600s (1 hr) |
| `/sitemaps/locations.xml` | States and configured US cities | 3,600s (1 hr) |
| `/sitemaps/listings.xml` | Active, non-expired listings (chunked with `lastmod`) | 1,800s (30 min) |
| `/robots.txt` | Standardized robots directives linking to `/sitemap.xml` with crawl delays | Real-time |

---

## 3. Privacy-First Analytics Tracking (`AnalyticsTrackingService`)

LocalAdZone adheres to strict privacy standards without sacrificing actionable publisher telemetry:

- **Salted SHA-256 Anonymization:** Raw IP addresses and User-Agent strings are never stored in plaintext. They are salted with `config('app.key')` and hashed (`hash_hmac('sha256', $ip . '|' . $ua, config('app.key'))`) to generate ephemeral daily visitor hashes.
- **Unique View Deduping:** Daily unique impressions are recorded per listing without tracking identifiable personal data.
- **Interaction Telemetry:** Direct engagement actions (phone reveal clicks, protected contact message clicks, share clicks, favorite toggles) are recorded in `analytics_events`.
- **Search Trend Tracking:** Search queries are normalized, stripped of dangerous characters, and aggregated in `search_trends` with volume counts and result hit counters.

---

## 4. Multi-Channel Notifications & Preferences

Notifications are managed via `NotificationService` and respect user privacy and communication preferences:

### Notification Classes (`app/Modules/Notifications/Notifications/`):
1. `ListingSubmittedNotification`: Sent when a listing is queued for moderation review.
2. `ListingApprovedNotification`: Sent when a listing is approved and published.
3. `ListingRejectedNotification`: Sent with feedback and rejection reason.
4. `ListingExpiringNotification`: Sent 3 days before listing expiration with renewal instructions.
5. `ListingRenewedNotification`: Confirmation of 30-day listing extension.
6. `ContactMessageReceivedNotification`: Immediate alert of inquiry with sender name and subject (without revealing raw private emails).
7. `PaymentReceiptNotification`: Printable invoice and payment confirmation.

### Preference Engine:
- Users can granularly opt in or out of notification types via `UserPreference` (`notification_settings`).
- Critical security and transaction receipts bypass non-essential marketing opt-outs.
- All outbound dispatches are logged to `notifications_log` for deliverability auditing.

### Expiration Scheduler:
- Artisan command `php artisan listings:notify-expiring` scans listings expiring within 72 hours and dispatches reminder notifications.

