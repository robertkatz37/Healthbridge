<x-admin-layout title="{{ $family->user->name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.families.index') }}" class="hb-link">Families</a></li>
        <li class="breadcrumb-item active">{{ $family->user->name }}</li>
    @endslot

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Family Details</h6>
                    <dl style="font-size:0.85rem;">
                        <dt style="color:var(--hb-gray-600);">Email</dt>
                        <dd>{{ $family->user->email }}</dd>
                        <dt style="color:var(--hb-gray-600);">Phone</dt>
                        <dd>{{ $family->phone ?? 'Not provided' }}</dd>
                        <dt style="color:var(--hb-gray-600);">Relationship</dt>
                        <dd class="mb-0">{{ $family->relationship_to_seeker ?? 'Not specified' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Care Seekers</h6>
                    @forelse($family->careSeekers as $seeker)
                        <div style="font-size:0.875rem;padding:0.5rem 0;border-bottom:1px solid var(--hb-gray-200);">
                            {{ $seeker->full_name }}
                            @if($seeker->care_type_needed) <span class="hb-badge-verified">{{ $seeker->care_type_needed->label() }}</span> @endif
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No care seeker profiles yet.</p>
                    @endforelse
                </div>
            </div>
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Leads</h6>
                    @forelse($leads as $lead)
                        <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                            <a href="{{ route('admin.leads.show', $lead) }}" class="hb-link">{{ $lead->status->label() }}</a>
                            <span style="color:var(--hb-gray-600);">{{ $lead->advisor?->user?->name ?? 'Unassigned' }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No leads yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
