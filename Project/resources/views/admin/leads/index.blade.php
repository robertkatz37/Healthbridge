<x-admin-layout title="Lead Management">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Leads</li>
    @endslot

    @if(session('status') === 'lead-assigned')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i> Lead assigned successfully.
        </div>
    @endif

    @if($unassignedCount > 0)
        <div class="hb-alert hb-alert-warning mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>{{ $unassignedCount }}</strong> open {{ Str::plural('lead', $unassignedCount) }} unassigned.
            <a href="{{ route('admin.leads.index', ['unassigned' => 1]) }}" class="hb-link">View unassigned</a>
        </div>
    @endif

    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.leads.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="hb-form-label">Status</label>
                    <select name="status" class="hb-form-control">
                        <option value="">All</option>
                        @foreach(\App\Enums\LeadStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="hb-form-label">Advisor</label>
                    <select name="advisor_id" class="hb-form-control">
                        <option value="">All</option>
                        @foreach($advisors as $advisor)
                            <option value="{{ $advisor->id }}" {{ request('advisor_id') == $advisor->id ? 'selected' : '' }}>{{ $advisor->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" class="form-check-input" name="unassigned" value="1" id="unassignedOnly" {{ request('unassigned') ? 'checked' : '' }}>
                        <label class="form-check-label" for="unassignedOnly">Unassigned only</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary" style="border-radius:0.625rem;">
                        <i class="bi bi-filter me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($leads->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No leads found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Family</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Advisor</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Received</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leads as $lead)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);" class="align-middle">
                                    <td class="px-4 py-3 fw-bold" style="color:var(--hb-gray-900);">{{ $lead->family_name }}</td>
                                    <td class="px-4 py-3"><span class="hb-badge-verified">{{ $lead->status->label() }}</span></td>
                                    <td class="px-4 py-3">{{ $lead->advisor?->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $lead->created_at->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('admin.leads.show', $lead) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($leads->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">
                        {{ $leads->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>
