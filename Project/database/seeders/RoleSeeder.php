<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds all 14 roles from SRS §5 and the permission matrix from SRS §7.
 * Permissions are grouped by module (agencies.*, referrals.*, etc.) and
 * assigned to roles here rather than checked via inline role-string
 * comparisons anywhere in the codebase, per CODING_STANDARDS.md.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Platform / users
            'users.manage_all', 'roles.manage',
            // Agencies
            'agencies.manage_own', 'agencies.manage_all', 'agencies.moderate', 'agencies.view_leads',
            // Families / care seekers
            'care_seekers.manage_own', 'families.manage_all',
            // Advisor CRM
            'advisor.manage_assigned', 'advisor.manage_team', 'advisor.view_reports', 'leads.manage_all',
            // Referrals / commissions
            'referrals.create', 'referrals.manage', 'commissions.view', 'commissions.manage',
            'invoices.view', 'invoices.manage',
            // Reviews
            'reviews.submit', 'reviews.moderate', 'reviews.reply_own_agency',
            // CMS
            'cms.manage',
            // Subscriptions / billing
            'billing.manage_own', 'billing.manage_all',
            // Support
            'support.manage_own_tickets', 'support.manage_all_tickets',
            // Affiliate
            'affiliate.view_own_dashboard',
            // Platform admin
            'system.view_health', 'system.manage_settings', 'audit_logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'super_admin' => $permissions, // unrestricted
            'platform_admin' => [
                'users.manage_all', 'agencies.manage_all', 'agencies.moderate', 'families.manage_all',
                'referrals.manage', 'commissions.view', 'invoices.view', 'reviews.moderate', 'cms.manage',
                'support.manage_all_tickets', 'system.view_health', 'audit_logs.view',
                'leads.manage_all', 'advisor.manage_team',
            ],
            'advisor_manager' => [
                'advisor.manage_assigned', 'advisor.manage_team', 'advisor.view_reports',
                'referrals.manage', 'families.manage_all',
            ],
            'advisor' => [
                'advisor.manage_assigned', 'referrals.create', 'referrals.manage', 'care_seekers.manage_own',
            ],
            'billing_manager' => [
                'billing.manage_all', 'invoices.view', 'invoices.manage', 'commissions.view',
            ],
            'support_agent' => [
                'support.manage_all_tickets',
            ],
            'content_editor' => [
                'cms.manage',
            ],
            'moderator' => [
                'agencies.moderate', 'reviews.moderate',
            ],
            'accountant' => [
                'commissions.view', 'commissions.manage', 'invoices.view', 'billing.manage_all',
            ],
            'agency_owner' => [
                'agencies.manage_own', 'agencies.view_leads', 'billing.manage_own', 'reviews.reply_own_agency',
            ],
            'agency_staff' => [
                'agencies.view_leads',
            ],
            'family' => [
                'care_seekers.manage_own', 'reviews.submit', 'support.manage_own_tickets',
            ],
            'reviewer' => [
                'reviews.submit',
            ],
            'affiliate' => [
                'affiliate.view_own_dashboard',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
