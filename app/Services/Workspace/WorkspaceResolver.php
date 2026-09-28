<?php

namespace App\Services\Workspace;

use App\Models\User;

/**
 * Defines the platform's canonical "workspaces" and resolves which ones a
 * given user can access. A workspace is a distinct dashboard area — several
 * roles can map to the same workspace (e.g. `moderator` and `accountant`
 * both land in the `admin` workspace; a user holding both roles still has
 * exactly one workspace, not two).
 *
 * This is the single source of truth for role → workspace mapping. Nothing
 * else in the app should hard-code a role-to-dashboard-route relationship —
 * see ResolveWorkspace middleware and WorkspaceController.
 */
class WorkspaceResolver
{
    /**
     * @var array<string, array{label: string, icon: string, route: string, roles: string[], description: string}>
     */
    private const WORKSPACES = [
        'admin' => [
            'label' => 'Admin Panel',
            'icon' => 'bi-shield-lock',
            'route' => 'admin.dashboard',
            'roles' => [
                'super_admin', 'platform_admin', 'billing_manager',
                'support_agent', 'content_editor', 'moderator', 'accountant',
            ],
            'description' => 'Manage platform operations, users, and content.',
        ],
        'advisor' => [
            'label' => 'Advisor CRM',
            'icon' => 'bi-person-badge',
            'route' => 'advisor.dashboard',
            'roles' => ['advisor', 'advisor_manager'],
            'description' => 'Manage assigned families and referrals.',
        ],
        'agency' => [
            'label' => 'Agency Dashboard',
            'icon' => 'bi-building',
            'route' => 'agency.dashboard',
            'roles' => ['agency_owner', 'agency_staff'],
            'description' => 'Manage your agency listing, leads, and team.',
        ],
        'family' => [
            'label' => 'Family Dashboard',
            'icon' => 'bi-house-heart',
            'route' => 'family.dashboard',
            'roles' => ['family', 'reviewer'],
            'description' => 'Find and manage care for your loved ones.',
        ],
        'affiliate' => [
            'label' => 'Affiliate Dashboard',
            'icon' => 'bi-link-45deg',
            'route' => 'affiliate.dashboard',
            'roles' => ['affiliate'],
            'description' => 'Track your referrals and earnings.',
        ],
    ];

    /**
     * Every workspace this user currently has access to, keyed by workspace key.
     *
     * @return array<string, array{key: string, label: string, icon: string, route: string, description: string}>
     */
    public function availableWorkspaces(User $user): array
    {
        $available = [];

        foreach (self::WORKSPACES as $key => $workspace) {
            if ($user->hasAnyRole($workspace['roles'])) {
                $available[$key] = [
                    'key' => $key,
                    'label' => $workspace['label'],
                    'icon' => $workspace['icon'],
                    'route' => $workspace['route'],
                    'description' => $workspace['description'],
                ];
            }
        }

        return $available;
    }

    public function hasAccess(User $user, string $workspaceKey): bool
    {
        return array_key_exists($workspaceKey, $this->availableWorkspaces($user));
    }

    public function routeFor(string $workspaceKey): ?string
    {
        return self::WORKSPACES[$workspaceKey]['route'] ?? null;
    }

    public function exists(string $workspaceKey): bool
    {
        return array_key_exists($workspaceKey, self::WORKSPACES);
    }
}
