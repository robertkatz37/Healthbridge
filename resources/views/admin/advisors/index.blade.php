<x-admin-layout title="Advisors">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Advisors</li>
    @endslot

    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.advisors.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="hb-form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="hb-form-control" placeholder="Name or email...">
                </div>
                <div class="col-md-3">
                    <label class="hb-form-label">Status</label>
                    <select name="status" class="hb-form-control">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($advisors->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-person-badge" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No advisors found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Name</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Email</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Manager</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Leads</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($advisors as $advisor)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);" class="align-middle">
                                    <td class="px-4 py-3 fw-bold" style="color:var(--hb-gray-900);">{{ $advisor->user->name }}</td>
                                    <td class="px-4 py-3">{{ $advisor->user->email }}</td>
                                    <td class="px-4 py-3">{{ $advisor->manager?->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $advisor->leads_count }}</td>
                                    <td class="px-4 py-3">
                                        <span style="font-size:0.75rem;font-weight:600;background:{{ $advisor->is_active ? 'var(--hb-emerald-100)' : 'var(--hb-gray-200)' }};color:{{ $advisor->is_active ? 'var(--hb-emerald-700)' : 'var(--hb-gray-600)' }};padding:0.2rem 0.6rem;border-radius:999px;">
                                            {{ $advisor->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('admin.advisors.show', $advisor) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($advisors->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">{{ $advisors->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>
