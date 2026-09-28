<x-family-layout title="My Notes">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">My Notes</li>
    @endslot

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);position:sticky;top:1rem;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Add a Note</h6>
                    <form method="POST" action="{{ route('family.notes.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="hb-form-label">About (optional)</label>
                            <select name="care_seeker_id" class="hb-form-control">
                                <option value="">General note</option>
                                @foreach($careSeekers as $seeker)
                                    <option value="{{ $seeker->id }}">{{ $seeker->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="hb-form-label">Note</label>
                            <textarea name="note" rows="4" class="hb-form-control @error('note') is-invalid @enderror"
                                      placeholder="Reminders, questions to ask an agency, observations..." required></textarea>
                            @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">
                            <i class="bi bi-plus-lg me-2"></i>Add Note
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            @if($notes->isEmpty())
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-journal-text" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                        <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No notes yet. These are private and visible only to you.</p>
                    </div>
                </div>
            @else
                @foreach($notes as $note)
                    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    @if($note->careSeeker)
                                        <span class="hb-badge-verified mb-2 d-inline-block">{{ $note->careSeeker->full_name }}</span>
                                    @endif
                                    <p style="font-size:0.9rem;color:var(--hb-gray-900);margin-bottom:0.375rem;">{{ $note->note }}</p>
                                    <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $note->created_at->diffForHumans() }}</div>
                                </div>
                                <form method="POST" action="{{ route('family.notes.destroy', $note) }}" onsubmit="return confirm('Delete this note?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-family-layout>
