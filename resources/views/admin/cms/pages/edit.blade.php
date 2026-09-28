<x-admin-layout title="Edit — {{ $page->title }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cms.pages.index') }}" class="hb-link">Pages</a></li>
        <li class="breadcrumb-item active">{{ $page->title }}</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-4">
        @if($page->status !== 'published')
            <form method="POST" action="{{ route('admin.cms.pages.publish', $page) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.625rem;"><i class="bi bi-check-circle me-1"></i>Publish Now</button>
            </form>
        @endif
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#scheduleModal" style="border-radius:0.625rem;"><i class="bi bi-calendar-event me-1"></i>Schedule</button>
        @if($page->status !== 'archived')
            <form method="POST" action="{{ route('admin.cms.pages.archive', $page) }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm" style="border-radius:0.625rem;"><i class="bi bi-archive me-1"></i>Archive</button>
            </form>
        @endif
        @if($page->status !== 'draft')
            <form method="POST" action="{{ route('admin.cms.pages.revert-to-draft', $page) }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm" style="border-radius:0.625rem;">Revert to Draft</button>
            </form>
        @endif
        <form method="POST" action="{{ route('admin.cms.pages.destroy', $page) }}" onsubmit="return confirm('Delete this page?');" class="ms-auto">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm" style="border-radius:0.625rem;"><i class="bi bi-trash me-1"></i>Delete</button>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.cms.pages.update', $page) }}">
        @csrf @method('PUT')
        @include('admin.cms.pages._form', ['page' => $page])
    </form>

    @if($page->revisions->isNotEmpty())
        <div class="card mt-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-clock-history me-2"></i>Revision History ({{ $page->revisions->count() }})</h6>
                @foreach($page->revisions as $revision)
                    <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.85rem;">
                        <div>
                            <strong>{{ $revision->title }}</strong>
                            <span style="color:var(--hb-gray-600);"> — edited by {{ $revision->editor?->name ?? 'System' }}, {{ $revision->created_at->diffForHumans() }}</span>
                        </div>
                        <form method="POST" action="{{ route('admin.cms.pages.revisions.restore', [$page, $revision]) }}" onsubmit="return confirm('Restore the page to this revision? The current content will be saved as a new revision first.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">Restore</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="{{ route('admin.cms.pages.schedule', $page) }}">
                @csrf
                <div class="modal-header"><h6 class="modal-title">Schedule Page</h6></div>
                <div class="modal-body">
                    <label class="hb-form-label">Publish at</label>
                    <input type="datetime-local" name="scheduled_at" class="hb-form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule</button>
                </div>
            </form>
        </div></div>
    </div>
</x-admin-layout>
