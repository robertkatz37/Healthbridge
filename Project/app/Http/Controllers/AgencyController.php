<?php

namespace App\Http\Controllers;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\AgencyCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public-facing agency directory (search/listing/detail). Full search
 * with Meilisearch faceting arrives in Phase 18 — this is a straightforward
 * Eloquent-backed listing sufficient for the directory to function today.
 */
class AgencyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Agency::published()->with('category');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('code', $request->category));
        }

        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }

        $agencies = $query->orderByDesc('is_featured')->orderByDesc('review_score')->paginate(12)->withQueryString();
        $categories = AgencyCategory::active()->get();

        return view('agencies.index', compact('agencies', 'categories'));
    }

    public function show(Agency $agency): View
    {
        abort_unless($agency->status === AgencyStatus::Published, 404);

        $agency->load(['category', 'services.catalogService', 'hours', 'coverage', 'certifications', 'media', 'pricing']);

        return view('agencies.show', compact('agency'));
    }
}
