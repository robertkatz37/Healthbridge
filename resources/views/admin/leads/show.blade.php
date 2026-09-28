<x-admin-layout title="{{ $lead->family_name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.leads.index') }}" class="hb-link">Leads</a></li>
        <li class="breadcrumb-item active">{{ $lead->family_name }}</li>
    @endslot

    @if(session('status') === 'lead-assigned')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i> Lead assigned successfully.
        </div>
    @endif

    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold mb-1" style="color:var(--hb-gray-900);">{{ $lead->family_name }}</h5>
                    <span class="hb-badge-verified">{{ $lead->status->label() }}</span>
                    <div style="font-size:0.85rem;color:var(--hb-gray-600);margin-top:0.5rem;">
                        Source: {{ $lead->source->label() }}
                        @if($lead->careSeeker) &middot; Care Seeker: {{ $lead->careSeeker->full_name }} @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.leads.assign', $lead) }}" class="d-flex gap-2">
                    @csrf
                    <select name="advisor_id" class="hb-form-control" style="min-width:220px;" required>
                        <option value="">Assign to advisor...</option>
                        @foreach($advisors as $advisor)
                            <option value="{{ $advisor->id }}" {{ $lead->advisor_id === $advisor->id ? 'selected' : '' }}>
                                {{ $advisor->user->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" style="border-radius:0.625rem;">
                        <i class="bi bi-person-check me-1"></i>{{ $lead->advisor_id ? 'Reassign' : 'Assign' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Status History</h6>
                    @forelse($lead->statusHistory as $entry)
                        <div class="d-flex gap-3 py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div style="width:32px;height:32px;background:var(--hb-emerald-100);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-arrow-right" style="font-size:0.8rem;color:var(--hb-emerald-700);"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">
                                    {{ $entry->from_status ? ucfirst(str_replace('_',' ',$entry->from_status)) . ' → ' : '' }}{{ ucfirst(str_replace('_',' ',$entry->to_status)) }}
                                </div>
                                <div style="font-size:0.75rem;color:var(--hb-gray-600);margin-top:0.2rem;">
                                    {{ $entry->changedBy?->name ?? 'System' }} &middot; {{ $entry->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No status changes recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Assignment History</h6>
                    @forelse($lead->assignmentHistory as $entry)
                        <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                            <span>{{ $entry->advisor->user->name }}</span>
                            <span style="color:var(--hb-gray-600);font-size:0.775rem;">
                                {{ $entry->assigned_at->format('M d, Y') }}
                                @if($entry->unassigned_at) – {{ $entry->unassigned_at->format('M d, Y') }} @endif
                            </span>
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No assignment history.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
