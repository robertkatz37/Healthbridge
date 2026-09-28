<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-side Lead Inbox — queries the real referrals table (built in
 * Phase 2). It will show real data automatically once the Matching
 * Engine (Phase 12, done) and Referral Engine (Phase 13) begin creating
 * Referral rows; the UI/query layer doesn't need to change later.
 */
class LeadInboxController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('viewLeads', $agency);

        $query = $agency->referrals()->with(['family.user', 'careSeeker', 'advisor.user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $leads = $query->paginate(15)->withQueryString();

        return view('agency.leads', compact('agency', 'leads'));
    }
}
