<x-admin-layout title="User — {{ $user->name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}" class="hb-link">Users</a></li>
        <li class="breadcrumb-item active">{{ $user->name }}</li>
    @endslot

    <div class="row g-4">

        {{-- Left: Profile Card --}}
        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4 text-center">
                    <img src="{{ $user->avatar_url }}" alt="Avatar"
                         style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--hb-emerald-100);margin-bottom:1rem;">
                    <h5 class="fw-bold mb-1" style="color:var(--hb-gray-900);">{{ $user->name }}</h5>
                    <div style="font-size:0.875rem;color:var(--hb-gray-600);margin-bottom:1rem;">{{ $user->email }}</div>

                    @foreach($user->roles as $role)
                        <span class="hb-badge-verified me-1 mb-1 d-inline-block">
                            {{ str_replace('_', ' ', ucwords($role->name)) }}
                        </span>
                    @endforeach

                    <div class="mt-3 pt-3" style="border-top:1px solid var(--hb-gray-200);">
                        <div class="row text-center">
                            <div class="col-6">
                                <div style="font-size:0.7rem;color:var(--hb-gray-600);">Email Verified</div>
                                <div style="font-size:0.875rem;font-weight:600;color:{{ $user->email_verified_at ? 'var(--hb-success)' : 'var(--hb-warning)' }};">
                                    {{ $user->email_verified_at ? 'Yes' : 'No' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="font-size:0.7rem;color:var(--hb-gray-600);">2FA</div>
                                <div style="font-size:0.875rem;font-weight:600;color:{{ $user->hasTwoFactorEnabled() ? 'var(--hb-success)' : 'var(--hb-warning)' }};">
                                    {{ $user->hasTwoFactorEnabled() ? 'Enabled' : 'Disabled' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-3" style="border-top:1px solid var(--hb-gray-200);text-align:left;">
                        <div style="font-size:0.75rem;color:var(--hb-gray-600);">Member Since</div>
                        <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $user->created_at->format('M d, Y') }}</div>
                        @if($user->last_login_at)
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);margin-top:0.5rem;">Last Login</div>
                            <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $user->last_login_at->diffForHumans() }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Danger Zone --}}
            @can('delete', $user)
                @if($user->id !== auth()->id())
                    <div class="card mt-3" style="border-radius:1rem;border:1.5px solid #FCA5A5;">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-1 text-danger" style="font-size:0.875rem;">Delete User</h6>
                            <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:0.75rem;">
                                Permanently delete this account and all its data.
                            </p>
                            <button type="button" class="btn btn-sm btn-danger w-100" style="border-radius:0.625rem;"
                                    data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                <i class="bi bi-trash me-1"></i> Delete Account
                            </button>
                        </div>
                    </div>
                @endif
            @endcan
        </div>

        {{-- Right: Role Management + Login History --}}
        <div class="col-lg-8">

            {{-- Role Assignment --}}
            @can('assignRoles', $user)
                <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">
                            <i class="bi bi-shield-check me-2" style="color:var(--hb-emerald-700);"></i>Role Assignment
                        </h6>

                        <form method="POST" action="{{ route('admin.users.roles.update', $user) }}">
                            @csrf
                            @method('PUT')

                            <div class="row g-2 mb-3">
                                @foreach($roles as $role)
                                    <div class="col-md-4 col-6">
                                        <label class="d-flex align-items-center gap-2 p-2 rounded cursor-pointer"
                                               style="border:1.5px solid {{ $user->hasRole($role->name) ? 'var(--hb-emerald-500)' : 'var(--hb-gray-200)' }};
                                                      background:{{ $user->hasRole($role->name) ? 'var(--hb-emerald-100)' : 'white' }};
                                                      border-radius:0.625rem !important;cursor:pointer;transition:all 0.15s;">
                                            <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                                   {{ $user->hasRole($role->name) ? 'checked' : '' }}
                                                   style="accent-color:var(--hb-emerald-700);">
                                            <span style="font-size:0.8rem;font-weight:500;color:var(--hb-gray-900);">
                                                {{ str_replace('_', ' ', ucwords($role->name)) }}
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">
                                <i class="bi bi-save me-1"></i> Update Roles
                            </button>
                        </form>

                        @if(session('status') === 'roles-updated')
                            <div class="hb-alert hb-alert-success mt-3" data-auto-dismiss="4000">
                                <i class="bi bi-check-circle me-2"></i> Roles updated successfully.
                            </div>
                        @endif
                    </div>
                </div>
            @endcan

            {{-- Permissions Summary --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">
                        <i class="bi bi-key me-2" style="color:var(--hb-emerald-700);"></i>Effective Permissions
                    </h6>
                    <div class="d-flex flex-wrap gap-1">
                        @forelse($user->getAllPermissions() as $permission)
                            <span style="font-size:0.7rem;background:var(--hb-gray-50);border:1px solid var(--hb-gray-200);
                                         color:var(--hb-gray-600);padding:0.2rem 0.5rem;border-radius:0.375rem;font-family:monospace;">
                                {{ $permission->name }}
                            </span>
                        @empty
                            <span style="font-size:0.875rem;color:var(--hb-gray-600);">No permissions assigned.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Login History --}}
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">
                        <i class="bi bi-clock-history me-2" style="color:var(--hb-emerald-700);"></i>Recent Login Activity
                    </h6>
                    @forelse($user->loginHistories->take(5) as $entry)
                        <div class="hb-session-card">
                            <div class="hb-session-icon">
                                <i class="bi bi-{{ $entry->status === 'success' ? 'check-circle' : 'x-circle' }}"
                                   style="color:{{ $entry->status === 'success' ? 'var(--hb-success)' : 'var(--hb-danger)' }};"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">
                                    {{ $entry->device ?? 'Unknown Device' }}
                                </div>
                                <div style="font-size:0.775rem;color:var(--hb-gray-600);">
                                    {{ $entry->ip_address }} &middot; {{ $entry->logged_in_at->diffForHumans() }}
                                </div>
                            </div>
                            <span style="font-size:0.75rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;
                                background:{{ $entry->status === 'success' ? 'var(--hb-emerald-100)' : '#FEF2F2' }};
                                color:{{ $entry->status === 'success' ? 'var(--hb-emerald-700)' : '#991B1B' }};">
                                {{ ucfirst($entry->status) }}
                            </span>
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No login history recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    @can('delete', $user)
        @if($user->id !== auth()->id())
            <div class="modal fade" id="deleteUserModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius:1rem;border:none;">
                        <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                            <h6 class="modal-title fw-bold text-danger">Delete User Account</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                            @csrf
                            @method('DELETE')
                            <div class="modal-body">
                                <p style="font-size:0.875rem;color:var(--hb-gray-600);">
                                    You are about to permanently delete <strong>{{ $user->name }}</strong>
                                    (<code>{{ $user->email }}</code>). This action cannot be undone.
                                    Enter your own admin password to confirm.
                                </p>
                                <input type="password" name="password" class="hb-form-control"
                                       placeholder="Your password" required>
                            </div>
                            <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                                <button type="submit" class="btn btn-danger" style="border-radius:0.75rem;">
                                    <i class="bi bi-trash me-1"></i> Delete User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

</x-admin-layout>
