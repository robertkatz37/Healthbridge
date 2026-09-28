<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\AssignLeadRequest;
use App\Models\Advisor;
use App\Models\Lead;
use App\Services\Advisor\LeadAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Super Admin / Platform Admin lead oversight — "Manual Lead Assignment
 * by Super Admin". Distinct from Advisor\LeadController (which scopes to
 * the advisor's own/team leads); this controller sees every lead
 * platform-wide, gated by the leads.manage_all permission.
 */
class LeadController extends Controller
{
    public function __construct(
        private readonly LeadAssignmentService $assignment,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('leads.manage_all'), 403);

        $query = Lead::with(['family.user', 'careSeeker', 'advisor.user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('unassigned')) {
            $query->unassigned();
        }
        if ($request->filled('advisor_id')) {
            $query->where('advisor_id', $request->advisor_id);
        }

        $leads = $query->paginate(20)->withQueryString();
        $advisors = Advisor::active()->with('user')->get();
        $unassignedCount = Lead::unassigned()->open()->count();

        return view('admin.leads.index', compact('leads', 'advisors', 'unassignedCount'));
    }

    public function show(Request $request, Lead $lead): View
    {
        abort_unless($request->user()->can('leads.manage_all'), 403);

        $lead->load(['family.user', 'careSeeker', 'advisor.user', 'statusHistory.changedBy', 'assignmentHistory.advisor.user']);
        $advisors = Advisor::active()->with('user')->get();

        return view('admin.leads.show', compact('lead', 'advisors'));
    }

    public function assign(AssignLeadRequest $request, Lead $lead): RedirectResponse
    {
        $advisor = Advisor::findOrFail($request->advisor_id);

        $this->assignment->assign($lead, $advisor, $request->user());

        return back()->with('status', 'lead-assigned');
    }
}
