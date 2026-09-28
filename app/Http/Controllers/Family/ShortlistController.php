<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\MatchResult;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A dedicated cross-Care-Seeker view of every agency the family has
 * shortlisted (is_family_shortlisted=true) — distinct from the
 * per-Care-Seeker Recommendations page, where shortlisting actually
 * happens. Mirrors Favorites' index/toggle split (Phase 9): the action
 * lives on the recommendation card, this page is the "everything I've
 * shortlisted" summary, same pattern as Favorites and Compare.
 */
class ShortlistController extends Controller
{
    public function index(Request $request): View
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();

        $shortlisted = MatchResult::where('is_family_shortlisted', true)
            ->whereHas('needsAssessment.careSeeker', fn ($q) => $q->where('family_id', $family->id))
            ->with(['agency.category', 'needsAssessment.careSeeker'])
            ->get();

        return view('family.shortlist.index', compact('shortlisted'));
    }
}
