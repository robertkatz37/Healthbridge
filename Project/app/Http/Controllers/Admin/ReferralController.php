<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-side Referral oversight — mirrors the Advisors/Families pattern
 * from Phase 12's dashboard polish pass. Gated by the same
 * manage-platform-settings permission as Reports/Audit Log, since
 * viewing every referral platform-wide is a platform-administration
 * concern, not tied to a specific resource ownership check.
 */
class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('manage-platform-settings'), 403);

        $query = Referral::with(['family.user', 'careSeeker', 'agency', 'advisor.user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('agency', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhereHas('careSeeker', fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
        }

        $referrals = $query->paginate(25)->withQueryString();
        $statuses = ReferralStatus::cases();

        return view('admin.referrals.index', compact('referrals', 'statuses'));
    }

    public function show(Request $request, Referral $referral): View
    {
        abort_unless($request->user()->can('manage-platform-settings'), 403);

        $referral->load(['family.user', 'careSeeker', 'agency', 'advisor.user', 'statusHistory.changedBy', 'notes.author', 'tourRequests']);

        return view('admin.referrals.show', compact('referral'));
    }
}
