<?php

namespace App\Http\Controllers\Advisor;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\StoreLeadRequest;
use App\Http\Requests\Advisor\UpdateLeadStatusRequest;
use App\Models\Advisor;
use App\Models\Lead;
use App\Services\Advisor\LeadAssignmentService;
use App\Services\Advisor\LeadPipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadPipelineService $pipeline,
        private readonly LeadAssignmentService $assignment,
    ) {}

    /**
     * Lead Inbox — search & advanced filters (status, source, date range,
     * text search on family name). Scoped to the advisor's own leads, or
     * their team's leads if they manage a team (LeadPolicy::onActorsTeam).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();

        $query = Lead::with(['family.user', 'careSeeker'])
            ->where(function ($q) use ($advisor) {
                $q->where('advisor_id', $advisor->id);
                if ($advisor->teamMembers()->exists()) {
                    $q->orWhereIn('advisor_id', $advisor->teamMembers()->pluck('id'));
                }
            })
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('family.user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('care_type')) {
            $query->whereHas('careSeeker', fn ($q) => $q->where('care_type_needed', $request->care_type));
        }
        if ($request->filled('budget_min')) {
            $query->whereHas('careSeeker', fn ($q) => $q->where('budget_max', '>=', $request->budget_min));
        }
        if ($request->filled('budget_max')) {
            $query->whereHas('careSeeker', fn ($q) => $q->where('budget_min', '<=', $request->budget_max));
        }
        if ($request->filled('city')) {
            $query->where('territory_city', 'like', "%{$request->city}%");
        }
        if ($request->filled('state')) {
            $query->where('territory_state', $request->state);
        }
        if ($request->filled('move_in_timeline')) {
            $query->whereHas('careSeeker', fn ($q) => $q->where('move_in_timeline', $request->move_in_timeline));
        }
        if ($request->filled('assigned_date_from')) {
            $query->whereDate('assigned_at', '>=', $request->assigned_date_from);
        }
        if ($request->filled('assigned_date_to')) {
            $query->whereDate('assigned_at', '<=', $request->assigned_date_to);
        }
        if ($request->filled('advisor_id')) {
            // Only meaningful for an Advisor Manager filtering within
            // their own team — the outer where() above already scopes
            // results to "own or team's" leads, so this just narrows
            // which specific team member within that set.
            $query->where('advisor_id', $request->advisor_id);
        }

        $leads = $query->paginate(15)->withQueryString();
        $teamAdvisors = $advisor->teamMembers()->with('user')->get();

        return view('advisor.leads.index', compact('leads', 'teamAdvisors'));
    }

    public function show(Request $request, Lead $lead): View
    {
        $this->authorize('view', $lead);

        $lead->load([
            'family.user', 'careSeeker.needsAssessments', 'careSeeker.documents',
            'advisor.user', 'statusHistory.changedBy', 'assignmentHistory.advisor.user',
            'notes.advisor.user', 'tasks', 'tourRequests.agency', 'conversations.messages.sender',
        ]);

        $timeline = app(\App\Services\Advisor\LeadTimelineService::class)->forLead($lead);

        return view('advisor.leads.show', compact('lead', 'timeline'));
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = \App\Models\Lead::create($request->validated());

        $this->assignment->autoAssign($lead, $request->user());

        activity()->causedBy($request->user())->performedOn($lead)->log('Lead created');

        return redirect()->route('advisor.leads.show', $lead)->with('status', 'lead-created');
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): RedirectResponse
    {
        $this->pipeline->transition($lead, LeadStatus::from($request->status), $request->user(), $request->reason);

        return back()->with('status', 'lead-status-updated');
    }
}
