<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\StoreLeadTaskRequest;
use App\Models\Advisor;
use App\Models\AdvisorTask;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadTaskController extends Controller
{
    public function store(StoreLeadTaskRequest $request, Lead $lead): RedirectResponse
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();

        $lead->tasks()->create([
            'advisor_id' => $advisor->id,
            'family_id' => $lead->family_id,
            'title' => $request->title,
            'due_at' => $request->due_at,
        ]);

        activity()->causedBy($request->user())->performedOn($lead)->log('Task created');

        return back()->with('status', 'task-added');
    }

    public function complete(Request $request, AdvisorTask $task): RedirectResponse
    {
        $this->authorizeTaskAccess($request, $task);

        $task->update(['completed_at' => now()]);

        return back()->with('status', 'task-completed');
    }

    public function destroy(Request $request, AdvisorTask $task): RedirectResponse
    {
        $this->authorizeTaskAccess($request, $task);

        $task->delete();

        return back()->with('status', 'task-deleted');
    }

    private function authorizeTaskAccess(Request $request, AdvisorTask $task): void
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->first();
        $isOwnTask = $advisor && $task->advisor_id === $advisor->id;

        abort_unless($isOwnTask || $request->user()->can('leads.manage_all'), 403);
    }
}
