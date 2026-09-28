<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\RescheduleTourRequest;
use App\Http\Requests\Agency\ScheduleTourAsAgencyRequest;
use App\Models\Referral;
use App\Models\TourRequest;
use App\Services\Referral\TourManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Agency-side tour actions — Schedule, Reschedule, Complete, per the
 * Phase 13 Agency Workflow requirements. All delegate to
 * TourManagementService (Phase 13) so the Referral pipeline stays in
 * sync automatically regardless of which party actually schedules/
 * completes the tour.
 */
class TourController extends Controller
{
    public function __construct(
        private readonly TourManagementService $tours,
    ) {}

    /**
     * Dedicated Upcoming Tours page for the agency dashboard.
     */
    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $tours = TourRequest::where('agency_id', $agency->id)
            ->with(['family.user', 'careSeeker', 'referral'])
            ->upcoming()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('requested_date')
            ->paginate(20);

        return view('agency.tours.index', compact('tours'));
    }

    public function store(ScheduleTourAsAgencyRequest $request, Referral $referral): RedirectResponse
    {
        $this->tours->schedule($referral, $request->requested_date, $request->requested_time_window, $request->notes, $request->user());

        return back()->with('status', 'tour-scheduled');
    }

    public function reschedule(RescheduleTourRequest $request, TourRequest $tourRequest): RedirectResponse
    {
        $this->tours->reschedule($tourRequest, $request->requested_date, $request->requested_time_window, $request->user());

        return back()->with('status', 'tour-rescheduled');
    }

    public function confirm(Request $request, TourRequest $tourRequest): RedirectResponse
    {
        $this->authorize('update', $tourRequest);

        $this->tours->confirm($tourRequest, $request->user());

        return back()->with('status', 'tour-confirmed');
    }

    public function complete(Request $request, TourRequest $tourRequest): RedirectResponse
    {
        $this->authorize('update', $tourRequest);

        $this->tours->complete($tourRequest, $request->user());

        return back()->with('status', 'tour-completed');
    }
}
