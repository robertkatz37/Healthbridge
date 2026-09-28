<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Family-facing referral tracking — read-only by design. "Families
 * should never contact agencies directly": there is no accept/decline/
 * note-writing action here, only visibility into status, advisor
 * updates (notes explicitly marked visible_to_family), and scheduled
 * tours. Never touches MatchResult/matching scores.
 */
class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();

        $referrals = Referral::where('family_id', $family->id)
            ->with(['agency', 'careSeeker', 'advisor.user'])
            ->latest()
            ->paginate(20);

        return view('family.referrals.index', compact('referrals'));
    }

    public function show(Request $request, Referral $referral): View
    {
        $this->authorize('view', $referral);

        $referral->load(['agency', 'careSeeker', 'advisor.user', 'statusHistory', 'tourRequests']);

        $visibleNotes = $referral->notes()->visibleToFamily()->with('author')->get();

        return view('family.referrals.show', compact('referral', 'visibleNotes'));
    }
}
