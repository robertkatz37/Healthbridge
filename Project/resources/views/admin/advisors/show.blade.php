<x-admin-layout title="{{ $advisor->user->name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.advisors.index') }}" class="hb-link">Advisors</a></li>
        <li class="breadcrumb-item active">{{ $advisor->user->name }}</li>
    @endslot

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Advisor Details</h6>
                    <dl style="font-size:0.85rem;">
                        <dt style="color:var(--hb-gray-600);">Email</dt>
                        <dd>{{ $advisor->user->email }}</dd>
                        <dt style="color:var(--hb-gray-600);">Phone</dt>
                        <dd>{{ $advisor->phone ?? 'Not provided' }}</dd>
                        <dt style="color:var(--hb-gray-600);">License</dt>
                        <dd>{{ $advisor->license_number ?? 'Not provided' }}</dd>
                        <dt style="color:var(--hb-gray-600);">Manager</dt>
                        <dd>{{ $advisor->manager?->user?->name ?? 'None' }}</dd>
                        <dt style="color:var(--hb-gray-600);">Team Size</dt>
                        <dd class="mb-0">{{ $advisor->teamMembers->count() }}</dd>
                    </dl>
                </div>
            </div>
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);font-size:0.875rem;">Territories</h6>
                    @forelse($advisor->territories as $territory)
                        <div style="font-size:0.85rem;">{{ $territory->city }}, {{ $territory->state }}</div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No territories on file.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Recent Leads</h6>
                    @forelse($recentLeads as $lead)
                        <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                            <a href="{{ route('admin.leads.show', $lead) }}" class="hb-link">{{ $lead->family_name }}</a>
                            <span class="hb-badge-verified">{{ $lead->status->label() }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No leads yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
