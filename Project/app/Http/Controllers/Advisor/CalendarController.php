<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\Advisor;
use App\Models\TourRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Aggregates advisor_tasks (due_at) and tour_requests (requested_date)
 * into a single calendar view — no new table, reuses both existing data
 * sources.
 */
class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('view', $advisor);

        $month = $request->input('month', now()->format('Y-m'));
        $start = \Illuminate\Support\Carbon::parse($month . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $tasks = $advisor->tasks()
            ->whereBetween('due_at', [$start, $end])
            ->get()
            ->map(fn ($task) => [
                'type' => 'task', 'date' => $task->due_at->toDateString(),
                'title' => $task->title, 'id' => $task->id, 'completed' => (bool) $task->completed_at,
            ]);

        $leadIds = $advisor->leads()->pluck('id');
        $tours = TourRequest::whereIn('lead_id', $leadIds)
            ->whereBetween('requested_date', [$start->toDateString(), $end->toDateString()])
            ->with('agency')
            ->get()
            ->map(fn ($tour) => [
                'type' => 'tour', 'date' => $tour->requested_date->toDateString(),
                'title' => 'Tour: ' . ($tour->agency->name ?? 'Agency'), 'id' => $tour->id,
                'status' => $tour->status->value,
            ]);

        $events = $tasks->concat($tours)->groupBy('date');

        return view('advisor.calendar', compact('events', 'start'));
    }
}
