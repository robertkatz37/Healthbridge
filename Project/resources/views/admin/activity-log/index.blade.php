<x-admin-layout title="Audit Logs">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Audit Logs</li>
    @endslot

    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.activity-log.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="hb-form-label">Search description</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="hb-form-control">
                </div>
                <div class="col-md-3">
                    <label class="hb-form-label">Subject Type</label>
                    <select name="subject_type" class="hb-form-control">
                        <option value="">All</option>
                        @foreach($subjectTypes as $type)
                            <option value="{{ $type }}" {{ request('subject_type') === $type ? 'selected' : '' }}>{{ class_basename($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="hb-form-control">
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="hb-form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($activities->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-journal-text" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No activity found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.85rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Description</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Subject</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Caused By</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3" style="color:var(--hb-gray-900);">{{ $activity->description }}</td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $activity->subject_type ? class_basename($activity->subject_type) . ' #' . $activity->subject_id : '—' }}</td>
                                    <td class="px-4 py-3">{{ $activity->causer?->name ?? 'System' }}</td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $activity->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($activities->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">{{ $activities->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>
