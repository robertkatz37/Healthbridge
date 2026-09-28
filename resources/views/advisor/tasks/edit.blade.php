<x-advisor-layout title="Edit Task">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('advisor.tasks.index') }}" class="hb-link">Tasks</a></li>
        <li class="breadcrumb-item active">Edit</li>
    @endslot

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Edit Task</h6>
                    <form method="POST" action="{{ route('advisor.tasks.update', $task) }}" novalidate>
                        @csrf @method('PUT')

                        <div class="mb-3">
                            <label class="hb-form-label">Title</label>
                            <input type="text" name="title" class="hb-form-control @error('title') is-invalid @enderror" value="{{ old('title', $task->title) }}" required>
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="hb-form-label">Related lead</label>
                            <select name="lead_id" class="hb-form-control">
                                <option value="">None</option>
                                @foreach($leads as $lead)
                                    <option value="{{ $lead->id }}" {{ old('lead_id', $task->lead_id) == $lead->id ? 'selected' : '' }}>{{ $lead->family_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-md-8">
                                <label class="hb-form-label">Due date/time</label>
                                <input type="datetime-local" name="due_at" class="hb-form-control @error('due_at') is-invalid @enderror"
                                       value="{{ old('due_at', $task->due_at->format('Y-m-d\TH:i')) }}" required>
                                @error('due_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="hb-form-label">Priority</label>
                                <select name="priority" class="hb-form-control">
                                    @foreach(\App\Enums\TaskPriority::cases() as $priority)
                                        <option value="{{ $priority->value }}" {{ old('priority', $task->priority->value) === $priority->value ? 'selected' : '' }}>{{ $priority->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('advisor.tasks.index') }}" class="btn btn-light" style="border-radius:0.75rem;">Cancel</a>
                            <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-advisor-layout>
