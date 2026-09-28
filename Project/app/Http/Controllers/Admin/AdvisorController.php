<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advisor;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-side Advisors oversight — authorizes via the existing
 * AdvisorPolicy (Phase 11), no new policy needed: viewAny() already
 * grants access to leads.manage_all, which super_admin/platform_admin
 * hold.
 */
class AdvisorController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Advisor::class);

        $query = Advisor::with(['user', 'manager.user'])->withCount('leads')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $advisors = $query->paginate(20)->withQueryString();

        return view('admin.advisors.index', compact('advisors'));
    }

    public function show(Request $request, Advisor $advisor): View
    {
        $this->authorize('view', $advisor);

        $advisor->load(['user', 'manager.user', 'teamMembers.user', 'territories']);
        $recentLeads = $advisor->leads()->with('family.user')->latest()->limit(10)->get();

        return view('admin.advisors.show', compact('advisor', 'recentLeads'));
    }
}
