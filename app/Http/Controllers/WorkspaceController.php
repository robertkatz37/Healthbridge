<?php

namespace App\Http\Controllers;

use App\Services\Workspace\WorkspaceResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceResolver $workspaces,
    ) {}

    /**
     * Shows the Workspace Selector. If the user only has one workspace
     * (or none), skip the screen entirely and route them directly —
     * this page should only ever be seen by genuinely multi-workspace users,
     * including when they explicitly click "Switch Workspace" later.
     */
    public function select(Request $request): View|RedirectResponse
    {
        $available = $this->workspaces->availableWorkspaces($request->user());

        if (count($available) <= 1) {
            return redirect()->route('dashboard');
        }

        return view('workspace.select', ['workspaces' => $available]);
    }

    /**
     * Enters a chosen workspace: validates access, remembers the choice in
     * the session so future logins skip the selector, then redirects.
     */
    public function enter(Request $request, string $workspace): RedirectResponse
    {
        if (!$this->workspaces->hasAccess($request->user(), $workspace)) {
            abort(403);
        }

        $request->session()->put('workspace', $workspace);

        $route = $this->workspaces->routeFor($workspace);

        activity()
            ->causedBy($request->user())
            ->withProperties(['workspace' => $workspace])
            ->log('Workspace selected');

        return redirect()->route($route);
    }
}
