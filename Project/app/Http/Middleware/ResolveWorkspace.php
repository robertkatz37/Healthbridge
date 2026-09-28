<?php

namespace App\Http\Middleware;

use App\Services\Workspace\WorkspaceResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces the old RoleBasedRedirect (single hard-coded role-priority list)
 * with workspace-aware routing per the multi-role platform standard:
 *
 *  - 0 workspaces: pass through to the generic dashboard view (edge case —
 *    an authenticated user with no dashboard-mapped role at all).
 *  - 1 workspace: redirect straight there. No selector shown — a
 *    single-workspace user should never see an unnecessary extra screen.
 *  - 2+ workspaces: check session for a previously remembered workspace
 *    (see WorkspaceController::enter()). If it's set and still valid for
 *    this user, redirect straight there — the selector is not re-shown on
 *    every login. If it's missing or no longer valid (e.g. the role was
 *    since revoked), send them to the Workspace Selector to choose.
 *
 * Only fires when the request is on the generic /dashboard route, to avoid
 * redirect loops on the workspace-specific dashboard routes themselves.
 */
class ResolveWorkspace
{
    public function __construct(
        private readonly WorkspaceResolver $workspaces,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->routeIs('dashboard')) {
            return $next($request);
        }

        $user = $request->user();
        $available = $this->workspaces->availableWorkspaces($user);

        if (count($available) === 0) {
            return $next($request);
        }

        if (count($available) === 1) {
            $only = array_key_first($available);
            return redirect()->route($available[$only]['route']);
        }

        $remembered = $request->session()->get('workspace');
        if ($remembered && array_key_exists($remembered, $available)) {
            return redirect()->route($available[$remembered]['route']);
        }

        return redirect()->route('workspace.select');
    }
}
