<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\StoreTourRequestRequest;
use App\Http\Requests\Advisor\UpdateTourRequestRequest;
use App\Models\Advisor;
use App\Models\Lead;
use App\Models\TourRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TourController extends Controller
{
    /**
     * Dedicated Tours page — upcoming and completed, across every lead
     * this advisor (or their team) owns. Distinct from the per-lead tour
     * list embedded on a Lead's detail page, which only shows that one
     * lead's tours.
     */
    public function index(Request $request): View
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('view', $advisor);

        $leadIds = Lead::where(function ($q) use ($advisor) {
            $q->where('advisor_id', $advisor->id);
            if ($advisor->teamMembers()->exists()) {
                $q->orWhereIn('advisor_id', $advisor->teamMembers()->pluck('id'));
            }
        })->pluck('id');

        $query = TourRequest::whereIn('lead_id', $leadIds)->with(['agency', 'lead.family.user']);

        if ($request->input('filter') === 'completed') {
            $query->where('status', 'completed');
        } elseif ($request->input('filter') === 'cancelled') {
            $query->where('status', 'cancelled');
        } else {
            $query->upcoming()->whereNotIn('status', ['completed', 'cancelled']);
        }

        $tours = $query->orderBy('requested_date')->paginate(20)->withQueryString();

        return view('advisor.tours.index', compact('tours'));
    }

    public function store(StoreTourRequestRequest $request, Lead $lead): RedirectResponse
    {
        $lead->tourRequests()->create([
            'family_id' => $lead->family_id,
            'care_seeker_id' => $lead->care_seeker_id,
            'agency_id' => $request->agency_id,
            'requested_date' => $request->requested_date,
            'requested_time_window' => $request->requested_time_window,
            'notes' => $request->notes,
        ]);

        activity()->causedBy($request->user())->performedOn($lead)->log('Tour scheduled');

        return back()->with('status', 'tour-scheduled');
    }

    /**
     * Reschedule — updates the date/time window/agency of an existing
     * tour rather than only being able to cancel and recreate one.
     */
    public function update(UpdateTourRequestRequest $request, TourRequest $tourRequest): RedirectResponse
    {
        $this->authorize('update', $tourRequest);

        $tourRequest->update($request->validated());

        activity()->causedBy($request->user())->performedOn($tourRequest)->log('Tour rescheduled');

        return back()->with('status', 'tour-updated');
    }

    public function updateStatus(Request $request, TourRequest $tourRequest): RedirectResponse
    {
        $this->authorize('update', $tourRequest);

        $request->validate(['status' => ['required', 'in:requested,confirmed,completed,cancelled']]);

        $tourRequest->update(['status' => $request->status]);

        activity()->causedBy($request->user())->performedOn($tourRequest)->log('Tour status updated to ' . $request->status);

        return back()->with('status', 'tour-updated');
    }

    public function destroy(Request $request, TourRequest $tourRequest): RedirectResponse
    {
        $this->authorize('delete', $tourRequest);

        $tourRequest->delete();

        return back()->with('status', 'tour-deleted');
    }
}
