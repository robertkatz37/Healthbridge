<?php

namespace App\Http\Controllers\Advisor;

use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\AddReferralNoteRequest;
use App\Http\Requests\Advisor\CancelReferralRequest;
use App\Http\Requests\Advisor\ScheduleTourForReferralRequest;
use App\Http\Requests\Advisor\SendReferralRequest;
use App\Http\Requests\Advisor\TransitionReferralRequest;
use App\Http\Requests\Advisor\UpdateReferralPriorityRequest;
use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Lead;
use App\Models\Referral;
use App\Services\Referral\ReferralCreationService;
use App\Services\Referral\ReferralPipelineService;
use App\Services\Referral\TourManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralCreationService $creation,
        private readonly ReferralPipelineService $pipeline,
        private readonly TourManagementService $tours,
    ) {}

    /**
     * The advisor's full Referral list — grouped exactly as requested:
     * Pending, Accepted, Declined, Tours Scheduled, Move-ins,
     * Conversions — via query-string filter rather than separate pages,
     * consistent with how the Lead Inbox filters work.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Referral::class);

        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();

        $query = Referral::with(['family.user', 'agency', 'careSeeker'])
            ->where(function ($q) use ($advisor) {
                $q->where('advisor_id', $advisor->id);
                if ($advisor->teamMembers()->exists()) {
                    $q->orWhereIn('advisor_id', $advisor->teamMembers()->pluck('id'));
                }
            })
            ->latest();

        $filter = $request->input('filter', 'all');
        match ($filter) {
            'pending' => $query->whereIn('status', [ReferralStatus::Pending->value, ReferralStatus::SentToAgency->value]),
            'accepted' => $query->where('status', ReferralStatus::AgencyAccepted->value),
            'declined' => $query->where('status', ReferralStatus::AgencyDeclined->value),
            'tours' => $query->whereIn('status', [ReferralStatus::TourScheduled->value, ReferralStatus::TourCompleted->value]),
            'move_ins' => $query->where('status', ReferralStatus::MoveInConfirmed->value),
            'conversions' => $query->where('status', ReferralStatus::Converted->value),
            default => null,
        };

        $referrals = $query->paginate(20)->withQueryString();

        // Summary counts for the filter tabs, scoped the same way as the
        // main query but unfiltered by status — computed once here
        // rather than N separate queries from the view.
        $baseQuery = Referral::where(function ($q) use ($advisor) {
            $q->where('advisor_id', $advisor->id);
            if ($advisor->teamMembers()->exists()) {
                $q->orWhereIn('advisor_id', $advisor->teamMembers()->pluck('id'));
            }
        });
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereIn('status', [ReferralStatus::Pending->value, ReferralStatus::SentToAgency->value])->count(),
            'accepted' => (clone $baseQuery)->where('status', ReferralStatus::AgencyAccepted->value)->count(),
            'declined' => (clone $baseQuery)->where('status', ReferralStatus::AgencyDeclined->value)->count(),
            'tours' => (clone $baseQuery)->whereIn('status', [ReferralStatus::TourScheduled->value, ReferralStatus::TourCompleted->value])->count(),
            'move_ins' => (clone $baseQuery)->where('status', ReferralStatus::MoveInConfirmed->value)->count(),
            'conversions' => (clone $baseQuery)->where('status', ReferralStatus::Converted->value)->count(),
        ];

        return view('advisor.referrals.index', compact('referrals', 'filter', 'counts'));
    }

    public function show(Request $request, Referral $referral): View
    {
        $this->authorize('view', $referral);

        $referral->load([
            'family.user', 'careSeeker', 'agency', 'advisor.user', 'lead',
            'statusHistory.changedBy', 'notes.author', 'tourRequests', 'commission',
        ]);

        $visibleNotes = $referral->notes; // advisor sees every note regardless of visibility flags

        return view('advisor.referrals.show', compact('referral', 'visibleNotes'));
    }

    /**
     * "Send Referral" — the single action that both formally creates the
     * Referral(s) and immediately sends them to the selected agency/
     * agencies, per Lead. Supports selecting multiple agencies at once
     * (one Referral row per agency).
     */
    public function store(SendReferralRequest $request, Lead $lead): RedirectResponse
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();
        $priority = ReferralPriority::from($request->priority);

        $created = 0;
        $errors = [];
        foreach ($request->agency_ids as $agencyId) {
            $agency = Agency::find($agencyId);
            try {
                $this->creation->sendReferral($lead, $agency, $advisor, $request->user(), $priority, $request->notes);
                $created++;
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errors[] = $agency->name . ': ' . collect($e->errors())->flatten()->first();
            }
        }

        if ($created === 0) {
            return back()->withErrors(['agency_ids' => implode(' ', $errors)]);
        }

        return redirect()->route('advisor.leads.show', $lead)
            ->with('status', 'referrals-sent')
            ->with('referral_errors', $errors);
    }

    public function updatePriority(UpdateReferralPriorityRequest $request, Referral $referral): RedirectResponse
    {
        $referral->update(['priority' => $request->priority]);

        activity()->causedBy($request->user())->performedOn($referral)->log('Referral priority updated to ' . $request->priority);

        return back()->with('status', 'referral-updated');
    }

    public function transition(TransitionReferralRequest $request, Referral $referral): RedirectResponse
    {
        $this->pipeline->transition($referral, ReferralStatus::from($request->status), $request->user(), $request->reason);

        return back()->with('status', 'referral-updated');
    }

    public function cancel(CancelReferralRequest $request, Referral $referral): RedirectResponse
    {
        $this->pipeline->transition($referral, ReferralStatus::Cancelled, $request->user(), $request->reason);

        return back()->with('status', 'referral-cancelled');
    }

    public function addNote(AddReferralNoteRequest $request, Referral $referral): RedirectResponse
    {
        $referral->notes()->create([
            'author_id' => $request->user()->id,
            'author_type' => 'advisor',
            'visible_to_agency' => $request->boolean('visible_to_agency'),
            'visible_to_family' => $request->boolean('visible_to_family'),
            'content' => $request->content,
        ]);

        activity()->causedBy($request->user())->performedOn($referral)->log('Advisor note added to referral');

        return back()->with('status', 'note-added');
    }

    public function scheduleTour(ScheduleTourForReferralRequest $request, Referral $referral): RedirectResponse
    {
        $this->tours->schedule($referral, $request->requested_date, $request->requested_time_window, $request->notes, $request->user());

        return back()->with('status', 'tour-scheduled');
    }
}
