<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-side Families oversight — authorizes via the existing
 * FamilyPolicy (Phase 9), no new policy needed.
 */
class FamilyController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Family::class);

        $query = Family::with('user')->withCount('careSeekers')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $families = $query->paginate(20)->withQueryString();

        return view('admin.families.index', compact('families'));
    }

    public function show(Request $request, Family $family): View
    {
        $this->authorize('view', $family);

        $family->load(['user', 'careSeekers']);
        $leads = $family->leads()->with('advisor.user')->latest()->get();

        return view('admin.families.show', compact('family', 'leads'));
    }
}
