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
            ['name' => 'Super Administrator', 'slug' => 'super_admin', 'description' => 'Full system access and infrastructure control.', 'is_system' => true],
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'General administration, configuration, and user management.', 'is_system' => true],
            ['name' => 'Senior Moderator', 'slug' => 'senior_moderator', 'description' => 'Supervises moderation queue, user suspensions, and appeals.', 'is_system' => true],
            ['name' => 'Listing Moderator', 'slug' => 'listing_moderator', 'description' => 'Reviews incoming listings, photos, and basic reports.', 'is_system' => true],
            ['name' => 'Compliance Officer', 'slug' => 'compliance_officer', 'description' => 'Enforces legal requests, DMCA, and anti-trafficking compliance.', 'is_system' => true],
            ['name' => 'Customer Support', 'slug' => 'customer_support', 'description' => 'Assists users with account and listing inquiries.', 'is_system' => false],
        ];

        $roleModels = [];
        foreach ($roles as $r) {
            $roleModels[$r['slug']] = Role::firstOrCreate(['slug' => $r['slug']], $r);
        }

        $permissions = [
            // Listings
            ['name' => 'View Listings', 'slug' => 'listings.view', 'module' => 'listings'],
            ['name' => 'Approve Listings', 'slug' => 'listings.approve', 'module' => 'listings'],
            ['name' => 'Reject Listings', 'slug' => 'listings.reject', 'module' => 'listings'],
            ['name' => 'Delete Listings', 'slug' => 'listings.delete', 'module' => 'listings'],
            
            // Moderation & Compliance
            ['name' => 'View Moderation Queue', 'slug' => 'moderation.view', 'module' => 'moderation'],
            ['name' => 'Suspend Users', 'slug' => 'moderation.suspend_users', 'module' => 'moderation'],
            ['name' => 'Handle Appeals', 'slug' => 'moderation.handle_appeals', 'module' => 'moderation'],
            ['name' => 'Compliance Escalations', 'slug' => 'moderation.compliance', 'module' => 'moderation'],

            // Categories & Locations
            ['name' => 'Manage Categories', 'slug' => 'categories.manage', 'module' => 'categories'],
            ['name' => 'Manage Locations', 'slug' => 'locations.manage', 'module' => 'locations'],

            // Billing & Pricing
            ['name' => 'Manage Pricing', 'slug' => 'billing.manage_pricing', 'module' => 'billing'],
            ['name' => 'Issue Refunds', 'slug' => 'billing.refunds', 'module' => 'billing'],

            // System & Settings
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'module' => 'settings'],
            ['name' => 'View Audit Logs', 'slug' => 'admin.audit_logs', 'module' => 'admin'],
        ];

        $allPermissionIds = [];
        foreach ($permissions as $p) {
            $perm = Permission::firstOrCreate(['slug' => $p['slug']], $p);
            $allPermissionIds[] = $perm->id;
        }

        // Grant all permissions to super_admin and admin
        $roleModels['super_admin']->permissions()->sync($allPermissionIds);
        $roleModels['admin']->permissions()->sync($allPermissionIds);

        // Moderator permissions
        $moderatorPerms = Permission::whereIn('slug', [
            'listings.view', 'listings.approve', 'listings.reject',
            'moderation.view', 'moderation.suspend_users',
        ])->pluck('id')->toArray();
        $roleModels['listing_moderator']->permissions()->sync($moderatorPerms);
    }
}
