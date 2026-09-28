<x-admin-layout title="User Management">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Users</li>
    @endslot

    {{-- Filters --}}
    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="hb-form-label">Search</label>
                    <div class="hb-input-icon-wrap">
                        <i class="bi bi-search hb-input-icon" style="font-size:0.9rem;"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="hb-form-control" placeholder="Name or email...">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="hb-form-label">Filter by Role</label>
                    <select name="role" class="hb-form-control">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                                {{ str_replace('_', ' ', ucwords($role->name)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;flex:1;">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    @if(request('search') || request('role'))
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary" style="border-radius:0.75rem;">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-header d-flex align-items-center justify-content-between py-3 px-4" style="background:none;border-bottom:1px solid var(--hb-gray-200);">
            <h6 class="mb-0 fw-bold" style="color:var(--hb-gray-900);">
                All Users
                <span style="font-size:0.75rem;font-weight:400;color:var(--hb-gray-600);margin-left:0.5rem;">
                    {{ number_format($users->total()) }} total
                </span>
            </h6>
        </div>
        <div class="card-body p-0">
            @if($users->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-people" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No users found matching your filters.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);border-bottom:1px solid var(--hb-gray-200);">
                            <tr>
                                <th class="px-4 py-3 fw-600" style="color:var(--hb-gray-600);font-weight:600;">User</th>
                                <th class="px-4 py-3 fw-600" style="color:var(--hb-gray-600);font-weight:600;">Roles</th>
                                <th class="px-4 py-3 fw-600" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3 fw-600" style="color:var(--hb-gray-600);font-weight:600;">Joined</th>
                                <th class="px-4 py-3 fw-600 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);" class="align-middle">
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="{{ $user->avatar_url }}" alt="Avatar"
                                                 style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                                            <div>
                                                <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $user->name }}</div>
                                                <div style="font-size:0.775rem;color:var(--hb-gray-600);">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @forelse($user->roles as $role)
                                            <span class="hb-badge-verified me-1">
                                                {{ str_replace('_', ' ', ucwords($role->name)) }}
                                            </span>
                                        @empty
                                            <span style="color:var(--hb-gray-600);font-size:0.775rem;">No role</span>
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($user->email_verified_at)
                                            <span style="background:var(--hb-emerald-100);color:var(--hb-emerald-700);font-size:0.75rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;">
                                                <i class="bi bi-check-circle me-1"></i>Verified
                                            </span>
                                        @else
                                            <span style="background:#FEF2F2;color:#991B1B;font-size:0.75rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;">
                                                <i class="bi bi-exclamation-circle me-1"></i>Unverified
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('admin.users.show', $user) }}"
                                           class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">
                        {{ $users->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>
