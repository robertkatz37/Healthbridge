<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — HealthsBridge</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body style="background:var(--hb-gray-50);font-family:'Inter',sans-serif;">

{{-- Top Navigation --}}
<nav class="navbar navbar-expand-lg" style="background:var(--hb-emerald-900);border-bottom:1px solid rgba(255,255,255,0.08);">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 text-white fw-bold"
           href="{{ route('dashboard') }}"
           style="font-family:'Fraunces',serif;font-size:1.2rem;text-decoration:none;">
            <div style="width:32px;height:32px;background:var(--hb-emerald-500);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-heart-pulse-fill" style="font-size:1rem;color:white;"></i>
            </div>
            Healths<span style="color:var(--hb-emerald-500);">Bridge</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                style="color:rgba(255,255,255,0.8);">
            <i class="bi bi-list" style="font-size:1.4rem;"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-center gap-1">

                @if(!auth()->user()->hasVerifiedEmail())
                    <li class="nav-item">
                        <a href="{{ route('verification.notice') }}" class="nav-link"
                           style="color:var(--hb-warning);font-size:0.875rem;font-weight:500;">
                            <i class="bi bi-exclamation-triangle me-1"></i>Verify Email
                        </a>
                    </li>
                @endif

                {{-- Admin Panel Link (staff roles only) --}}
                @can('view-admin-panel')
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link"
                           style="color:rgba(255,255,255,0.75);font-size:0.875rem;">
                            <i class="bi bi-grid me-1"></i>Admin
                        </a>
                    </li>
                @endcan

                {{-- Notifications --}}
                <li class="nav-item">
                    <a href="#" class="nav-link" style="color:rgba(255,255,255,0.65);">
                        <i class="bi bi-bell" style="font-size:1.1rem;"></i>
                    </a>
                </li>

                {{-- User Dropdown --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                       href="#" data-bs-toggle="dropdown" style="color:white;">
                        <img src="{{ auth()->user()->avatar_url }}" alt="Avatar"
                             style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.2);">
                        <span class="d-none d-lg-inline" style="font-size:0.875rem;font-weight:500;">
                            {{ auth()->user()->name }}
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end"
                        style="border-radius:0.875rem;border:none;box-shadow:0 8px 32px rgba(0,0,0,0.12);min-width:220px;">
                        <li class="px-3 py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">Signed in as</div>
                            <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ auth()->user()->email }}</div>
                            <div style="margin-top:0.3rem;">
                                @foreach(auth()->user()->getRoleNames() as $role)
                                    <span class="hb-badge-verified">
                                        {{ str_replace('_', ' ', ucfirst($role)) }}
                                    </span>
                                @endforeach
                            </div>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person-circle me-2" style="color:var(--hb-gray-600);"></i>My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('two-factor.setup') }}">
                                <i class="bi bi-shield-lock me-2" style="color:var(--hb-gray-600);"></i>Security (2FA)
                            </a>
                        </li>
                        @can('view-admin-panel')
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-grid me-2" style="color:var(--hb-gray-600);"></i>Admin Panel
                                </a>
                            </li>
                        @endcan
                        @if(count($availableWorkspaces ?? []) > 1)
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('workspace.select') }}">
                                    <i class="bi bi-grid-3x3-gap me-2" style="color:var(--hb-gray-600);"></i>Switch Workspace
                                </a>
                            </li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger">
                                    <i class="bi bi-box-arrow-left me-2"></i>Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

{{-- Page Content --}}
<main class="container-fluid px-4 py-4">
    @if(session('status'))
        @php
            $statusMap = [
                'profile-updated'  => 'Profile updated successfully.',
                'password-updated' => 'Password changed successfully.',
                'avatar-updated'   => 'Profile photo updated.',
                '2fa-disabled'     => 'Two-factor authentication has been disabled.',
            ];
            $msg = $statusMap[session('status')] ?? session('status');
        @endphp
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i> {{ $msg }}
        </div>
    @endif

    {{ $slot }}
</main>

@stack('scripts')
</body>
</html>
