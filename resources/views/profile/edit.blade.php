<x-app-layout title="My Profile">

    @if(session('status') === '2fa-disabled')
        <div class="hb-alert hb-alert-warning mb-4" data-auto-dismiss="5000">⚠ Two-factor authentication has been disabled.</div>
    @endif

    <div class="row g-4">
        {{-- Left: Avatar + nav --}}
        <div class="col-lg-3">
            <div class="card text-center" style="border-radius:1rem;border:none;box-shadow:0 2px 12px rgba(6,61,46,0.06);padding:1.5rem;">
                <div class="hb-avatar-upload d-inline-block mb-3">
                    <img src="{{ $user->avatar_url }}" alt="Avatar" class="hb-avatar">
                    <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" id="avatarForm">
                        @csrf
                        <label class="hb-avatar-btn" title="Change photo" for="avatarInput">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </label>
                        <input type="file" id="avatarInput" name="avatar" class="d-none" accept="image/*"
                               onchange="document.getElementById('avatarForm').submit()">
                    </form>
                </div>
                <div class="fw-bold" style="font-size:1rem;color:var(--hb-gray-900);">{{ $user->name }}</div>
                <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:0.75rem;">{{ $user->email }}</div>
                @foreach($user->getRoleNames() as $role)
                    <span class="hb-badge-verified">{{ str_replace('_', ' ', ucfirst($role)) }}</span>
                @endforeach
                @if($user->hasVerifiedEmail())
                    <div style="font-size:0.75rem;color:var(--hb-success);margin-top:0.5rem;">✓ Email verified</div>
                @else
                    <div style="font-size:0.75rem;color:var(--hb-warning);margin-top:0.5rem;">⚠ Email not verified</div>
                @endif
            </div>
        </div>

        {{-- Right: Forms --}}
        <div class="col-lg-9">

            {{-- Profile Info --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 12px rgba(6,61,46,0.06);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Profile Information</h6>

                    @if(session('status') === 'profile-updated')
                        <div class="hb-alert hb-alert-success mb-3" data-auto-dismiss="4000">✓ Profile updated successfully.</div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}" novalidate>
                        @csrf
                        @method('PATCH')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="hb-form-label">Full name</label>
                                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}"
                                       class="hb-form-control @error('name') is-invalid @enderror" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="hb-form-label">Email address</label>
                                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}"
                                       class="hb-form-control @error('email') is-invalid @enderror" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @if($user->family)
                            <div class="col-md-6">
                                <label for="phone" class="hb-form-label">Phone number</label>
                                <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->family->phone) }}"
                                       class="hb-form-control @error('phone') is-invalid @enderror">
                            </div>
                            @endif
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Change Password --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 12px rgba(6,61,46,0.06);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Change Password</h6>

                    @if(session('status') === 'password-updated')
                        <div class="hb-alert hb-alert-success mb-3" data-auto-dismiss="4000">✓ Password updated successfully.</div>
                    @endif

                    <form method="POST" action="{{ route('profile.password') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="current_password" class="hb-form-label">Current password</label>
                                <input id="current_password" type="password" name="current_password"
                                       class="hb-form-control @error('current_password') is-invalid @enderror">
                                @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="hb-form-label">New password</label>
                                <input id="new_password" type="password" name="password"
                                       class="hb-form-control @error('password') is-invalid @enderror">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="hb-form-label">Confirm new password</label>
                                <input id="password_confirmation" type="password" name="password_confirmation"
                                       class="hb-form-control">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Security / 2FA --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 12px rgba(6,61,46,0.06);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Two-Factor Authentication</h6>
                        @if($user->hasTwoFactorEnabled())
                            <span class="hb-badge-verified">Enabled</span>
                        @else
                            <span style="font-size:0.75rem;font-weight:600;background:#FEF2F2;color:#991B1B;padding:0.2rem 0.6rem;border-radius:999px;">Not enabled</span>
                        @endif
                    </div>
                    <p style="font-size:0.875rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                        Add an extra layer of security by requiring a code from your authenticator app when you sign in.
                    </p>
                    <a href="{{ route('two-factor.setup') }}" class="btn btn-outline-primary" style="border-radius:0.75rem;">
                        {{ $user->hasTwoFactorEnabled() ? 'Manage 2FA' : 'Enable 2FA' }}
                    </a>
                </div>
            </div>

            {{-- Login History --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 12px rgba(6,61,46,0.06);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Recent Sign-in Activity</h6>
                    @forelse($history as $entry)
                        <div class="hb-session-card {{ $loop->first ? 'current' : '' }}">
                            <div class="hb-session-icon">
                                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">
                                    {{ $entry->device ?? 'Unknown Device' }}
                                    @if($loop->first) <span class="hb-badge-verified ms-1">Current</span> @endif
                                </div>
                                <div style="font-size:0.775rem;color:var(--hb-gray-600);">
                                    {{ $entry->ip_address }} · {{ $entry->logged_in_at->diffForHumans() }}
                                </div>
                            </div>
                            <span style="font-size:0.75rem;font-weight:600;padding:0.2rem 0.6rem;border-radius:999px;
                                background:{{ $entry->status === 'success' ? 'var(--hb-emerald-100)' : '#FEF2F2' }};
                                color:{{ $entry->status === 'success' ? 'var(--hb-emerald-700)' : '#991B1B' }};">
                                {{ ucfirst($entry->status) }}
                            </span>
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No login history yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="card" style="border-radius:1rem;border:1.5px solid #FCA5A5;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1 text-danger">Delete Account</h6>
                    <p style="font-size:0.875rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                        Once deleted, your account and all associated data cannot be recovered.
                    </p>
                    <button type="button" class="btn btn-danger" style="border-radius:0.75rem;"
                            data-bs-toggle="modal" data-bs-target="#deleteModal">
                        Delete Account
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">Confirm account deletion</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">
                            This action is permanent and cannot be undone. Enter your password to confirm.
                        </p>
                        <input type="password" name="password" class="hb-form-control" placeholder="Your current password" required>
                        @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-danger" style="border-radius:0.75rem;">Delete My Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
