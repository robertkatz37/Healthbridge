<?php

namespace App\Http\Controllers\Agency;

use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\AddAgencyNoteRequest;
use App\Http\Requests\Agency\RespondToReferralRequest;
use App\Models\Referral;
use App\Services\Referral\ReferralPipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Agency's Referral Inbox — the real, populated version of what
 * Agency\LeadInboxController (Phase 7) was always querying against,
 * finally receiving live data now that Referrals are actually created.
 * Agencies never see matching scores, weights, or the algorithm — this
 * controller (and its view) only ever touches Referral/ReferralNote/
 * TourRequest data, never MatchResult.
 */
class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralPipelineService $pipeline,
    ) {}

    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $query = Referral::where('agency_id', $agency->id)
            ->with(['family.user', 'careSeeker', 'advisor.user'])
            ->latest();

        $filter = $request->input('filter', 'incoming');
        match ($filter) {
            'incoming' => $query->where('status', ReferralStatus::SentToAgency->value),
            'accepted' => $query->where('status', ReferralStatus::AgencyAccepted->value),
            'declined' => $query->where('status', ReferralStatus::AgencyDeclined->value),
            'tours' => $query->whereIn('status', [ReferralStatus::TourScheduled->value, ReferralStatus::TourCompleted->value]),
            'closed' => $query->whereIn('status', [ReferralStatus::Converted->value, ReferralStatus::ClosedLost->value]),
            default => null,
        };

        $referrals = $query->paginate(20)->withQueryString();

        $counts = [
            'incoming' => Referral::where('agency_id', $agency->id)->where('status', ReferralStatus::SentToAgency->value)->count(),
            'accepted' => Referral::where('agency_id', $agency->id)->where('status', ReferralStatus::AgencyAccepted->value)->count(),
            'tours' => Referral::where('agency_id', $agency->id)->whereIn('status', [ReferralStatus::TourScheduled->value, ReferralStatus::TourCompleted->value])->count(),
        ];

        return view('agency.referrals.index', compact('referrals', 'filter', 'counts'));
    }

    public function show(Request $request, Referral $referral): View
    {
        $this->authorize('view', $referral);

        $referral->load(['family.user', 'careSeeker', 'advisor.user', 'statusHistory', 'tourRequests']);

        // "View Advisor Notes (only shared notes)" + the agency's own
        // notes — never notes marked internal-only.
        $visibleNotes = $referral->notes()->visibleToAgency()->with('author')->get();

        // "View Family Contact Information only after referral is
        // accepted" — enforced here, not just hidden by CSS, so the
        // data never reaches the view/response before acceptance.
        $showFamilyContact = in_array($referral->status, [
            ReferralStatus::AgencyAccepted, ReferralStatus::TourScheduled, ReferralStatus::TourCompleted,
            ReferralStatus::FollowUpRequired, ReferralStatus::MoveInConfirmed, ReferralStatus::Converted,
        ], true);

        return view('agency.referrals.show', compact('referral', 'visibleNotes', 'showFamilyContact'));
    }

    public function accept(RespondToReferralRequest $request, Referral $referral): RedirectResponse
    {
        $this->pipeline->transition($referral, ReferralStatus::AgencyAccepted, $request->user());

        return back()->with('status', 'referral-accepted');
    }

    public function decline(RespondToReferralRequest $request, Referral $referral): RedirectResponse
    {
        $this->pipeline->transition($referral, ReferralStatus::AgencyDeclined, $request->user(), $request->reason);

        return back()->with('status', 'referral-declined');
    }

    /**
     * "Request More Information" — doesn't change the pipeline stage,
     * just adds a note visible to the advisor and (optionally) notifies
     * them, so the agency can ask a question without having to accept
     * or decline yet.
     */
    public function requestMoreInfo(AddAgencyNoteRequest $request, Referral $referral): RedirectResponse
    {
        $referral->notes()->create([
            'author_id' => $request->user()->id,
            'author_type' => 'agency',
            'visible_to_agency' => true,
            'visible_to_family' => false,
            'content' => '[Requesting more information] ' . $request->content,
        ]);

        activity()->causedBy($request->user())->performedOn($referral)->log('Agency requested more information');

        return back()->with('status', 'info-requested');
    }

    public function addNote(AddAgencyNoteRequest $request, Referral $referral): RedirectResponse
    {
        $referral->notes()->create([
            'author_id' => $request->user()->id,
            'author_type' => 'agency',
            'visible_to_agency' => true,
            'visible_to_family' => $request->boolean('visible_to_family'),
            'content' => $request->content,
        ]);

        activity()->causedBy($request->user())->performedOn($referral)->log('Agency note added to referral');

        return back()->with('status', 'note-added');
    }
}
