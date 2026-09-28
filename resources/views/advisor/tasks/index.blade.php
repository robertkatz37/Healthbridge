<x-advisor-layout title="Tasks">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Tasks</li>
    @endslot

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2">
            <a href="{{ route('advisor.tasks.index') }}" class="btn btn-sm {{ !request('filter') || request('filter') === 'pending' ? 'btn-primary' : 'btn-light' }}" style="border-radius:0.625rem;">Pending</a>
            <a href="{{ route('advisor.tasks.index', ['filter' => 'overdue']) }}" class="btn btn-sm {{ request('filter') === 'overdue' ? 'btn-primary' : 'btn-light' }}" style="border-radius:0.625rem;">Overdue</a>
            <a href="{{ route('advisor.tasks.index', ['filter' => 'completed']) }}" class="btn btn-sm {{ request('filter') === 'completed' ? 'btn-primary' : 'btn-light' }}" style="border-radius:0.625rem;">Completed</a>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal" style="border-radius:0.625rem;">
            <i class="bi bi-plus-lg me-1"></i>New Task
        </button>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($tasks->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-check2-circle" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No tasks here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Task</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Lead</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Priority</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Due</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);" class="align-middle">
                                    <td class="px-4 py-3" style="{{ $task->completed_at ? 'text-decoration:line-through;color:var(--hb-gray-600);' : 'color:var(--hb-gray-900);font-weight:600;' }}">
                                        {{ $task->title }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($task->lead)
                                            <a href="{{ route('advisor.leads.show', $task->lead) }}" class="hb-link">{{ $task->lead->family_name }}</a>
                                        @else
                                            <span style="color:var(--hb-gray-600);">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span style="font-size:0.7rem;font-weight:700;color:{{ $task->priority->color() }};">
                                            <i class="bi bi-flag-fill me-1"></i>{{ $task->priority->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3" style="color:{{ !$task->completed_at && $task->due_at->isPast() ? 'var(--hb-danger)' : 'var(--hb-gray-600)' }};">
                                        {{ $task->due_at->format('M d, g:ia') }}
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            @if($task->completed_at)
                                                <form method="POST" action="{{ route('advisor.tasks.reopen', $task) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">Reopen</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('advisor.tasks.complete', $task) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;"><i class="bi bi-check2"></i></button>
                                                </form>
                                            @endif
                                            <a href="{{ route('advisor.tasks.edit', $task) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;"><i class="bi bi-pencil"></i></a>
                                            <form method="POST" action="{{ route('advisor.tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($tasks->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">{{ $tasks->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">New Task</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('advisor.tasks.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="hb-form-label">Title</label>
                            <input type="text" name="title" class="hb-form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="hb-form-label">Related lead (optional)</label>
                            <select name="lead_id" class="hb-form-control">
                                <option value="">None</option>
                                @foreach($leads as $lead)
                                    <option value="{{ $lead->id }}">{{ $lead->family_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label class="hb-form-label">Due date/time</label>
                                <input type="datetime-local" name="due_at" class="hb-form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="hb-form-label">Priority</label>
                                <select name="priority" class="hb-form-control">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Create Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-advisor-layout>
