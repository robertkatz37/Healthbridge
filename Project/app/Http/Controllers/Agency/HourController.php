<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\UpdateHoursRequest;
use App\Models\AgencyHour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HourController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $hours = $agency->hours()->orderBy('day_of_week')->get()->keyBy('day_of_week');

        return view('agency.hours', compact('agency', 'hours'));
    }

    public function update(UpdateHoursRequest $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);

        foreach ($request->input('days') as $dayOfWeek => $day) {
            AgencyHour::updateOrCreate(
                ['agency_id' => $agency->id, 'day_of_week' => $dayOfWeek],
                [
                    'is_closed' => isset($day['is_closed']),
                    'open_time' => $day['open_time'] ?? null,
                    'close_time' => $day['close_time'] ?? null,
                ]
            );
        }

        activity()->causedBy($request->user())->performedOn($agency)->log('Business hours updated');

        return back()->with('status', 'hours-updated');
    }
}
