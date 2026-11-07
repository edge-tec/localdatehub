# Phase 11 — Enterprise Administration System Architecture

## 1. Executive Overview

The **Enterprise Administration System** provides a production-grade, multi-role, tamper-evident administrative portal for **LocalAdZone** / **LocalDateHub**. Designed under strict principle-of-least-privilege RBAC (Role-Based Access Control) guidelines, the system unifies **21 operational administrative modules** into a single cohesive, Livewire-driven control hub.

Every single administrative action—from listing approvals and user suspensions to fee refunds and setting changes—is strictly validated against granular permission slugs and permanently committed to the immutable `admin_audit_logs` table with full before/after state diffs, administrator identity, timestamp, IP address, and browser user agent.

---

## 2. Granular Role-Based Access Control (8 Staff Roles)

The platform enforces 8 distinct administrative tiers:

| Role Slug | Display Name | Core Purpose | Access Scope |
|---|---|---|---|
| `super_admin` | Super Administrator | Root operations & infrastructure | Unrestricted system-wide access, role reassignment, system configuration, gateway management. |
| `admin` | Administrator | General administration | All operational modules, user management, listings, catalogs, settings (except restricted payment keys). |
| `senior_moderator` | Senior Moderator | Trust & safety supervisory | Moderation queue, user suspensions, suspension appeals, age verification, abuse reports, audit logs. |
| `listing_moderator` | Listing Moderator | Content moderation | Incoming listing queues, approval/rejection workflows, listing suspensions. Prohibited from billing and settings. |
| `compliance_officer` | Compliance Officer | Legal, age & safety compliance | 18+ age verification audit, DMCA reports, anti-trafficking enforcement, disciplinary suspensions. |
| `customer_support` | Customer Support | User relations | Read access to users and listings, inquiry review, report triage. |
| `finance_manager` | Finance Manager | Monetization & revenue | Pricing plans, subscriptions, transaction ledger, refund authorization, financial analytics. |
| `seo_manager` | SEO Manager | Organic discovery & content | Meta tag templates, canonical directives, CMS informational pages, category SEO descriptions. |

---

## 3. Strict Boundary & Security Rules

1. **Zero-Tolerance Financial & Credential Boundary:** Normal moderators, customer support, and compliance officers are strictly blocked (`abort(403)`) from payment settings, refund tools, or system security configurations.
2. **Super Admin Guarding:** Only accounts with the primary role of `super_admin` can grant or revoke the `super_admin` role, or modify restricted payment/security settings groups. Administrators cannot suspend themselves or other administrators.
3. **Double-Layer Authorization:** Protected at both HTTP routing middleware level (`admin.rbac:permission_slug`) and component lifecycle action level (`authorizePermission('permission_slug')`).
4. **Active Account Enforcement:** Suspended staff accounts are immediately intercepted by `AdminRbacMiddleware` and redirected to `/account/suspended`.

---

## 4. Complete 21 Administrative Modules Architecture

| # | Module | Route / Key | Primary Capabilities |
|---|---|---|---|
| **1** | **Executive Dashboard** | `tab=dashboard` | Executive KPIs (accounts, active, pending listings, published, rejected, expired, reports, moderation queue, revenue, views), system health probes (DB, cache, queue, PHP/Laravel versions), and recent activity feed. |
| **2** | **Analytics** | `tab=analytics` | 30-day views, contact clicks, favorites, user acquisition, listing creation, revenue, refunds, top listings by impressions, and category breakdown. |
| **3** | **User Management** | `tab=users` | Member search, status filter (active, suspended, closed), role filter, temporary/permanent suspension modal with audit trail, and role assignment. |
| **4** | **Listings Management** | `tab=listings` | Search, status filtering, category filtering, single approve, reject with feedback modal, disciplinary suspension, soft-delete, and bulk approve/suspend. |
| **5** | **Categories** | `tab=categories` | Dynamic category tree, listing counts, active/inactive toggle, and links to category builder. |
| **6** | **Category Attributes** | `tab=attributes` | Dynamic custom fields, required/filterable flags, option counts, and field options editor. |
| **7** | **Locations Engine** | `tab=locations` | State directory, city counts, active toggle, and links to ZIP code/alias management. |
| **8** | **Moderation Queue** | `tab=moderation` | Chronological pending listing review queue with direct approve/reject actions and suspension appeals triage. |
| **9** | **Age Verification (18+)** | `tab=verification` | 18+ age verification queue (government ID, self-attestation, credit token), reviewer assignment, and approve/reject workflows updating user verification status. |
| **10** | **Reports & Complaints** | `tab=reports` | Safety and abuse reports queue (minor exploitation, commercial solicitation, harassment, scams), investigation notes, and resolution actions. |
| **11** | **Contact Abuse** | `tab=abuse` | Review of flagged inquiry messages with sender/recipient details and dismiss flag actions. |
| **12** | **Pricing Plans** | `tab=pricing` | Pricing tier management (listing upgrades, memberships, bump passes), price cents, duration, and active toggle. |
| **13** | **Featured Listings** | `tab=featured` | Active spotlights (homepage, city top, category top), schedule window, and premature deactivation. |
| **14** | **Subscriptions** | `tab=subscriptions` | Active recurring membership passes, gateway IDs, period tracking, and administrative cancellation. |
| **15** | **Payments & Refunds** | `tab=payments` | Payment transaction ledger, status filtering (completed, refunded, failed), and full refund issuance with audit logging. |
| **16** | **Advertisements** | `tab=ads` | House/sponsor banner management (`ad_placements`), slot selection, impression/click counters, and live toggles. |
| **17** | **SEO Management** | `tab=seo` | Custom SEO meta title, description, keywords, canonical URLs, and robots directives per page route. |
| **18** | **CMS Pages** | `tab=cms` | Static legal and informational pages (About, Terms, 18 U.S.C. 2257 Notice, Privacy, Safety Tips) with sanitized HTML editing and publish toggles. |
| **19** | **Staff Directory** | `tab=staff` | Dedicated staff team roster showing assigned roles, account statuses, last login timestamps, and revoke privileges actions. |
| **20** | **Roles & Permissions Matrix** | `tab=roles` | Matrix view mapping all 8 roles across 22 granular permissions grouped by module, with toggle actions restricted to Super Admin. |
| **21** | **System Configuration** | `tab=settings` | Key/value configuration editor grouped by general, safety, monetization, locations, security, and payment. |
| **22** | **Audit Trail** | `tab=audit` | Immutable chronological event log with action filter, admin filter, and detailed JSON before/after state diff inspection modal. |

---

## 5. Key Architecture Files

- **Livewire Component:** [`app/Livewire/Admin/AdminEnterpriseHub.php`](file:///Volumes/EDGE/Localdatehub/app/Livewire/Admin/AdminEnterpriseHub.php)
- **Component Template:** [`resources/views/livewire/admin/admin-enterprise-hub.blade.php`](file:///Volumes/EDGE/Localdatehub/resources/views/livewire/admin/admin-enterprise-hub.blade.php)
- **Admin Layout:** [`resources/views/components/layouts/admin.blade.php`](file:///Volumes/EDGE/Localdatehub/resources/views/components/layouts/admin.blade.php)
- **Controller:** [`app/Http/Controllers/Admin/AdminDashboardController.php`](file:///Volumes/EDGE/Localdatehub/app/Http/Controllers/Admin/AdminDashboardController.php)
- **View:** [`resources/views/admin/dashboard.blade.php`](file:///Volumes/EDGE/Localdatehub/resources/views/admin/dashboard.blade.php)
- **Middleware:** [`app/Http/Middleware/AdminRbacMiddleware.php`](file:///Volumes/EDGE/Localdatehub/app/Http/Middleware/AdminRbacMiddleware.php)
- **Audit Service:** [`app/Modules/Admin/Services/AdminAuditService.php`](file:///Volumes/EDGE/Localdatehub/app/Modules/Admin/Services/AdminAuditService.php)
- **Ad Placements Model & Migration:**
  - [`app/Modules/Admin/Models/AdPlacement.php`](file:///Volumes/EDGE/Localdatehub/app/Modules/Admin/Models/AdPlacement.php)
  - [`database/migrations/2026_11_08_000019_create_ad_placements_table.php`](file:///Volumes/EDGE/Localdatehub/database/migrations/2026_11_08_000019_create_ad_placements_table.php)
- **RBAC Seeder:** [`database/seeders/RoleAndPermissionSeeder.php`](file:///Volumes/EDGE/Localdatehub/database/seeders/RoleAndPermissionSeeder.php)
- **Routes:** Registered in [`routes/web.php`](file:///Volumes/EDGE/Localdatehub/routes/web.php) under `admin.*` with `['auth', 'admin.rbac']`.

---

## 6. Verification & Automated Test Coverage

Suite: [`tests/Feature/EnterpriseAdminSystemTest.php`](file:///Volumes/EDGE/Localdatehub/tests/Feature/EnterpriseAdminSystemTest.php) (18/18 passing, 70 assertions):
1. `test_unauthenticated_visitor_is_redirected_to_login_when_accessing_admin`
2. `test_standard_user_receives_403_forbidden_when_accessing_admin`
3. `test_suspended_staff_user_is_redirected_to_suspended_notice`
4. `test_super_admin_can_access_admin_dashboard_and_see_kpi_metrics`
5. `test_granular_rbac_listing_moderator_can_access_moderation_tab_but_forbidden_from_settings`
6. `test_granular_rbac_listing_moderator_forbidden_from_billing_refunds_and_system_settings`
7. `test_super_admin_can_suspend_and_unsuspend_a_user_creating_audit_log`
8. `test_staff_can_approve_and_reject_listing_with_audit_trail`
9. `test_staff_can_bulk_approve_listings_with_audit_trail`
10. `test_compliance_officer_can_approve_and_reject_18_plus_age_verification`
11. `test_staff_can_resolve_content_report_with_notes`
12. `test_finance_manager_can_issue_refund_creating_refund_record_and_audit_trail`
13. `test_finance_manager_cannot_refund_already_refunded_transaction`
14. `test_seo_manager_can_create_and_update_cms_pages_and_seo_metas`
15. `test_super_admin_can_update_system_settings_with_audit_log`
16. `test_non_super_admin_blocked_from_modifying_restricted_settings`
17. `test_admin_can_inspect_audit_log_details`
18. `test_staff_can_create_and_toggle_advertisements`

