<x-admin-layout title="Families">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Families</li>
    @endslot

    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.families.index') }}" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="hb-form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="hb-form-control" placeholder="Name or email...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($families->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-people" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No families found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Name</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Email</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Phone</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Care Seekers</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Joined</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($families as $family)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);" class="align-middle">
                                    <td class="px-4 py-3 fw-bold" style="color:var(--hb-gray-900);">{{ $family->user->name }}</td>
                                    <td class="px-4 py-3">{{ $family->user->email }}</td>
                                    <td class="px-4 py-3">{{ $family->phone ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $family->care_seekers_count }}</td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $family->created_at->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('admin.families.show', $family) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($families->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">{{ $families->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>
