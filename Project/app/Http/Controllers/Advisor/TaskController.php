<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\StoreAdvisorTaskRequest;
use App\Http\Requests\Advisor\UpdateAdvisorTaskRequest;
use App\Models\Advisor;
use App\Models\AdvisorTask;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The single source of truth for task CRUD, used both by the dedicated
 * "Tasks" page (index/create/edit/standalone tasks with no lead) and by
 * the quick-add form on a Lead's detail page (store/complete/destroy with
 * lead_id filled in) — consolidated here rather than splitting task
 * logic between this controller and the narrower Lead-scoped one from
 * the initial Phase 11 pass, per the completion audit.
 */
class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AdvisorTask::class);

        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();

        $query = AdvisorTask::with('lead.family.user')
            ->where(function ($q) use ($advisor) {
                $q->where('advisor_id', $advisor->id);
                if ($advisor->teamMembers()->exists()) {
                    $q->orWhereIn('advisor_id', $advisor->teamMembers()->pluck('id'));
                }
            });

        if ($request->input('filter') === 'completed') {
            $query->completed();
        } elseif ($request->input('filter') === 'overdue') {
            $query->overdue();
        } else {
            $query->pending();
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->orderBy('due_at')->paginate(20)->withQueryString();
        $leads = Lead::where('advisor_id', $advisor->id)->open()->with('family.user')->get();

        return view('advisor.tasks.index', compact('tasks', 'leads'));
    }

    public function store(StoreAdvisorTaskRequest $request): RedirectResponse
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();

        $task = $advisor->tasks()->create([
            'lead_id' => $request->lead_id,
            'title' => $request->title,
            'due_at' => $request->due_at,
            'priority' => $request->priority,
        ]);

        activity()->causedBy($request->user())->performedOn($task)->log('Task created');

        return back()->with('status', 'task-added');
    }

    public function edit(Request $request, AdvisorTask $task): View
    {
        $this->authorize('update', $task);

        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();
        $leads = Lead::where('advisor_id', $advisor->id)->open()->with('family.user')->get();

        return view('advisor.tasks.edit', compact('task', 'leads'));
    }

    public function update(UpdateAdvisorTaskRequest $request, AdvisorTask $task): RedirectResponse
    {
        $task->update($request->validated());

        activity()->causedBy($request->user())->performedOn($task)->log('Task updated');

        return redirect()->route('advisor.tasks.index')->with('status', 'task-updated');
    }

    public function complete(Request $request, AdvisorTask $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update(['completed_at' => now()]);

        activity()->causedBy($request->user())->performedOn($task)->log('Task completed');

        return back()->with('status', 'task-completed');
    }

    public function reopen(Request $request, AdvisorTask $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update(['completed_at' => null]);

        return back()->with('status', 'task-reopened');
    }

    public function destroy(Request $request, AdvisorTask $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return back()->with('status', 'task-deleted');
    }
}
