<x-advisor-layout title="Notes">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Notes</li>
    @endslot

    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('advisor.notes.index') }}" class="btn btn-sm {{ !request('filter') ? 'btn-primary' : 'btn-light' }}" style="border-radius:0.625rem;">All</a>
        <a href="{{ route('advisor.notes.index', ['filter' => 'notes']) }}" class="btn btn-sm {{ request('filter') === 'notes' ? 'btn-primary' : 'btn-light' }}" style="border-radius:0.625rem;">Lead Notes</a>
        <a href="{{ route('advisor.notes.index', ['filter' => 'internal']) }}" class="btn btn-sm {{ request('filter') === 'internal' ? 'btn-primary' : 'btn-light' }}" style="border-radius:0.625rem;">Internal Comments</a>
    </div>

    @if($notes->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-journal-text" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No notes yet.</p>
            </div>
        </div>
    @else
        @foreach($notes as $note)
            <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="hb-badge-verified">{{ $note->note_type->label() }}</span>
                        @if($note->is_internal)
                            <span style="font-size:0.7rem;font-weight:600;background:var(--hb-gray-200);color:var(--hb-gray-600);padding:0.15rem 0.5rem;border-radius:999px;">
                                <i class="bi bi-lock-fill me-1"></i>Internal
                            </span>
                        @endif
                        @if($note->lead)
                            <a href="{{ route('advisor.leads.show', $note->lead) }}" class="hb-link" style="font-size:0.8rem;">{{ $note->lead->family_name }}</a>
                        @endif
                    </div>
                    <p style="font-size:0.9rem;color:var(--hb-gray-900);margin-bottom:0.25rem;">{{ $note->content }}</p>
                    <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $note->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @endforeach

        @if($notes->hasPages())
            <div class="d-flex justify-content-center">{{ $notes->links() }}</div>
        @endif
    @endif
</x-advisor-layout>
