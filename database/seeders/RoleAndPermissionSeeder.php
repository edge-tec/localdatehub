<?php

namespace Database\Seeders;

use App\Modules\Admin\Models\Permission;
use App\Modules\Admin\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Administrator', 'slug' => 'super_admin', 'description' => 'Full root system access and infrastructure control.', 'is_system' => true],
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'General administration, configuration, and user management.', 'is_system' => true],
            ['name' => 'Senior Moderator', 'slug' => 'senior_moderator', 'description' => 'Supervises moderation queue, user suspensions, and appeals.', 'is_system' => true],
            ['name' => 'Listing Moderator', 'slug' => 'listing_moderator', 'description' => 'Reviews incoming listings, photos, and basic reports.', 'is_system' => true],
            ['name' => 'Compliance Officer', 'slug' => 'compliance_officer', 'description' => 'Enforces legal requests, 18+ age verification audit, DMCA, and anti-trafficking compliance.', 'is_system' => true],
            ['name' => 'Customer Support', 'slug' => 'customer_support', 'description' => 'Assists users with account, listing, and general inquiries.', 'is_system' => false],
            ['name' => 'Finance Manager', 'slug' => 'finance_manager', 'description' => 'Manages pricing tiers, subscriptions, transactions, and refund authorizations.', 'is_system' => false],
            ['name' => 'SEO Manager', 'slug' => 'seo_manager', 'description' => 'Manages search engine metadata, landing page content, and CMS pages.', 'is_system' => false],
        ];

        $roleModels = [];
        foreach ($roles as $r) {
            $roleModels[$r['slug']] = Role::updateOrCreate(['slug' => $r['slug']], $r);
        }

        $permissions = [
            // Users & Staff
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users'],
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'module' => 'users'],
            ['name' => 'Suspend Users', 'slug' => 'users.suspend', 'module' => 'users'],
            ['name' => 'Manage Staff & Roles', 'slug' => 'staff.manage', 'module' => 'staff'],

            // Listings
            ['name' => 'View Listings', 'slug' => 'listings.view', 'module' => 'listings'],
            ['name' => 'Approve Listings', 'slug' => 'listings.approve', 'module' => 'listings'],
            ['name' => 'Reject Listings', 'slug' => 'listings.reject', 'module' => 'listings'],
            ['name' => 'Suspend Listings', 'slug' => 'listings.suspend', 'module' => 'listings'],
            ['name' => 'Delete Listings', 'slug' => 'listings.delete', 'module' => 'listings'],

            // Categories & Locations
            ['name' => 'Manage Categories', 'slug' => 'categories.manage', 'module' => 'categories'],
            ['name' => 'Manage Locations', 'slug' => 'locations.manage', 'module' => 'locations'],

            // Moderation & Compliance
            ['name' => 'View Moderation Queue', 'slug' => 'moderation.view', 'module' => 'moderation'],
            ['name' => 'Act on Moderation', 'slug' => 'moderation.act', 'module' => 'moderation'],
            ['name' => 'Handle Appeals', 'slug' => 'moderation.handle_appeals', 'module' => 'moderation'],
            ['name' => 'Manage Age Verifications', 'slug' => 'verification.manage', 'module' => 'verification'],
            ['name' => 'Manage Abuse Reports', 'slug' => 'reports.manage', 'module' => 'reports'],

            // Billing & Pricing
            ['name' => 'View Billing & Revenue', 'slug' => 'billing.view', 'module' => 'billing'],
            ['name' => 'Manage Pricing Plans', 'slug' => 'billing.manage_pricing', 'module' => 'billing'],
            ['name' => 'Authorize Refunds', 'slug' => 'billing.refunds', 'module' => 'billing'],

            // SEO & CMS
            ['name' => 'Manage SEO Settings', 'slug' => 'seo.manage', 'module' => 'seo'],
            ['name' => 'Manage CMS Pages', 'slug' => 'cms.manage', 'module' => 'cms'],
            ['name' => 'Manage Advertisements', 'slug' => 'ads.manage', 'module' => 'ads'],

            // Analytics & Audit Logs
            ['name' => 'View Analytics', 'slug' => 'analytics.view', 'module' => 'analytics'],
            ['name' => 'View Audit Logs', 'slug' => 'audit.view', 'module' => 'admin'],

            // System Configuration
            ['name' => 'Manage System Settings', 'slug' => 'settings.manage', 'module' => 'settings'],
        ];

        $allPermissionIds = [];
        $permissionModelMap = [];
        foreach ($permissions as $p) {
            $perm = Permission::updateOrCreate(['slug' => $p['slug']], $p);
            $allPermissionIds[] = $perm->id;
            $permissionModelMap[$p['slug']] = $perm->id;
        }

        // Grant all permissions to super_admin and admin
        $roleModels['super_admin']->permissions()->sync($allPermissionIds);
        $roleModels['admin']->permissions()->sync($allPermissionIds);

        // Senior Moderator
        $roleModels['senior_moderator']->permissions()->sync(array_intersect_key($permissionModelMap, array_flip([
            'users.view', 'users.suspend', 'listings.view', 'listings.approve', 'listings.reject',
            'listings.suspend', 'moderation.view', 'moderation.act', 'moderation.handle_appeals',
            'verification.manage', 'reports.manage', 'audit.view',
        ])));

        // Listing Moderator
        $roleModels['listing_moderator']->permissions()->sync(array_intersect_key($permissionModelMap, array_flip([
            'listings.view', 'listings.approve', 'listings.reject', 'listings.suspend',
            'moderation.view', 'moderation.act',
        ])));

        // Compliance Officer
        $roleModels['compliance_officer']->permissions()->sync(array_intersect_key($permissionModelMap, array_flip([
            'users.view', 'users.suspend', 'listings.view', 'listings.suspend',
            'moderation.view', 'moderation.act', 'verification.manage', 'reports.manage', 'audit.view',
        ])));

        // Customer Support
        $roleModels['customer_support']->permissions()->sync(array_intersect_key($permissionModelMap, array_flip([
            'users.view', 'listings.view', 'reports.manage',
        ])));

        // Finance Manager
        $roleModels['finance_manager']->permissions()->sync(array_intersect_key($permissionModelMap, array_flip([
            'billing.view', 'billing.manage_pricing', 'billing.refunds', 'ads.manage', 'analytics.view', 'audit.view',
        ])));

        // SEO Manager
        $roleModels['seo_manager']->permissions()->sync(array_intersect_key($permissionModelMap, array_flip([
            'seo.manage', 'cms.manage', 'categories.manage', 'analytics.view',
        ])));
    }
}
