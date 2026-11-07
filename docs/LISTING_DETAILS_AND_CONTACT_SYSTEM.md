# Phase 09 — Listing Details and Contact System Architecture

## 1. Executive Architectural Overview

The **Listing Details and Contact System** provides a privacy-first, secure, and compliant communication layer for LocalDateHub's personal classifieds marketplace. Operating strictly within lawful adult personal connections and dating (with strict zero tolerance for commercial sex work, prostitution, human trafficking, and underage involvement), the system ensures that classified listings are presented clearly, location is protected via anti-triangulation fuzzing, and user-to-user interactions remain private, encrypted, and moderated.

---

## 2. Key Components & Implementation

### 2.1. Listing Detail Page (`resources/views/listings/show.blade.php`)
- **Breadcrumb Hierarchy:** Navigates from `Home` &rarr; `Categories` &rarr; `Parent Category` &rarr; `Subcategory` &rarr; `Listing Title`.
- **Verified Photo Gallery & Lightbox:** Renders approved photos (`$listing->approvedMedia`) with EXIF metadata stripped at upload. Includes an interactive full-screen photo lightbox modal powered by Alpine.js.
- **Dynamic Attribute Showcase:** Formats EAV attributes dynamically (e.g., relationship intent, lifestyle choices, smoker/non-smoker, education).
- **Advertiser Profile Card:** Highlights verified 18+ age status and identity verification badges without revealing private user credentials.
- **Location & Anti-Triangulation Shield:** Displays city and state while clarifying that raw GPS coordinates are fuzzed by 300–800m to prevent residential stalking or triangulation.
- **Similar Listings Discovery:** Displays 3 relevant active classifieds in the same category or geographic market using `<x-listing-card>`.
- **Saved Favorites Toggle:** Authenticated users can favorite/unfavorite listings (`POST /listings/{listing}/favorite`), maintaining counts and updating user lists.

---

### 2.2. Contact & Privacy Architecture

The platform supports external and protected contact workflows while strictly omitting real-time chat:

1. **A. External Contact Options:**
   - **Masked Anonymous Email Relay:** Configurable masked addresses (`relay-xxxx@reply.localdatehub.com`) preventing direct email exposure.
   - **External Handles:** Verified messaging handles (Signal, Telegram) displayed only when authorized by the poster.
   - **Phone/SMS Visibility:** Phone numbers are only visible when the advertiser explicitly enables `show_phone`.

2. **B. Protected Contact Form:**
   - **Honeypot Protection:** Employs hidden `contact_hp` input to intercept automated spambots without user friction.
   - **Rate Limiting:** Guarded by `throttle:5,10` middleware (maximum 5 inquiries per 10 minutes per IP/session).
   - **AES-256 Encryption:** Sender email, phone, and inquiry messages are encrypted in `contact_messages` using application encryption keys.
   - **FOSTA-SESTA & Scam Guardrails:** Pre-submission regex scanners block commercial sex work terms (`escort`, `prostitut`, `ppm`, `hourly rate`, `incall`, `outcall`, `sensual massage`), romance scam patterns (`wire transfer`, `western union`, `crypto`, `cash app`), and extortion phrases.

3. **C. Private Message Inbox (`/account/messages`):**
   - **User Inbox Interface:** Lists inquiries received across user listings, with filters for unread status, replied status, or specific listing IDs.
   - **Decrypted Reader (`/account/messages/{message}`):** Authorized recipient can read the decrypted inquiry, access sender email/phone, and reply directly via native mail client (`mailto:`). Automatically marks messages as read upon viewing with timestamps.
   - **Message Actions:** Allows users to mark inquiries as replied, flag unwanted messages to moderation with specific violation tags, or soft-delete them from their inbox.
   - **Authorization Policy (`ContactMessagePolicy`):** Enforces strict multi-tenant authorization ensuring users can only view, update, or delete their own messages.

4. **D. Abuse Reporting & Admin Moderation Queue (`/admin/moderation/messages`):**
   - **Confidential Reporting Modal:** Users can report listings with categories: `underage_minor`, `commercial_sex`, `non_consensual_content`, `fraud_scam`, `harassment`, `other`.
   - **Automated Escalation:** Reports regarding `underage_minor` or `commercial_sex` automatically change the target listing's status to `pending_review` and log a moderation action.
   - **Trust & Safety Moderation Dashboard:** Administrators review flagged messages and safety reports, inspect target listings, dismiss false flags, or suspend violating listings.

---

## 3. Database & Route Registrations

### Routes (`routes/web.php`):
- `GET /listings/{slug}` &rarr; `ListingWebController@show`
- `POST /listings/{listing}/contact` &rarr; `ListingContactWebController@submitInquiry` (`throttle:5,10`)
- `POST /listings/{listing}/report` &rarr; `ListingContactWebController@reportListing` (`throttle:5,10`)
- `POST /listings/{listing}/favorite` &rarr; `ListingContactWebController@toggleFavorite`
- `GET /account/messages` &rarr; `UserMessagesController@index`
- `GET /account/messages/{message}` &rarr; `UserMessagesController@show`
- `POST /account/messages/{message}/replied` &rarr; `UserMessagesController@markReplied`
- `POST /account/messages/{message}/flag` &rarr; `UserMessagesController@flag`
- `DELETE /account/messages/{message}` &rarr; `UserMessagesController@destroy`
- `GET /admin/moderation/messages` &rarr; `ModerationMessagesController@index`
- `POST /admin/moderation/messages/{message}/dismiss` &rarr; `ModerationMessagesController@dismiss`
- `POST /admin/moderation/messages/{message}/action` &rarr; `ModerationMessagesController@takeAction`

---

## 4. Automated Test Verification

Test coverage is established in `tests/Feature/ProtectedContactMessagingEngineTest.php`:
- Verified rendering of details, breadcrumbs, similar ads, and privacy notices.
- Verified protected inquiry encryption, incrementing replies, and recording analytics.
- Verified honeypot spam detection and rejection.
- Verified FOSTA-SESTA commercial sex work block and financial scam prevention.
- Verified report submission and automatic review suspension for high-severity violations.
- Verified favorite toggle on/off and authentication barriers.
- Verified user message inbox, read state timestamps, reply marking, flagging, and deletion.
- Verified admin moderation queue actions and listing suspension.

