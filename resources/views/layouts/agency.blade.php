<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Agency' }} — HealthsBridge</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <style>
        html, body { margin: 0; padding: 0; width: 100%; height: 100%; overflow-x: hidden; }
        *, *::before, *::after { box-sizing: border-box; }

        /* ── Sidebar ─────────────────────────────────────────────────── */
        .hb-sidebar {
            position: fixed; top: 0; left: 0;
            width: 260px; height: 100vh;
            background: var(--hb-emerald-900);
            display: flex; flex-direction: column;
            overflow: hidden; z-index: 1000;
            transition: transform .3s ease;
        }
        .hb-sidebar-logo {
            flex-shrink: 0; display: flex; align-items: center; gap: .75rem;
            padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .hb-sidebar-logo-mark {
            width: 36px; height: 36px; border-radius: 9px;
            background: var(--hb-emerald-500); display: flex;
            justify-content: center; align-items: center;
        }
        .hb-sidebar-logo-text { font-family: 'Fraunces', serif; font-size: 1.1rem; font-weight: 700; color: #fff; line-height: 1; }
        .hb-sidebar-logo-text span { color: var(--hb-emerald-500); }
        .hb-sidebar-nav {
            flex: 1; overflow-y: auto; overflow-x: hidden; padding: .5rem .75rem;
            scrollbar-width: none; -ms-overflow-style: none;
        }
        .hb-sidebar-nav::-webkit-scrollbar { display: none; }
        .hb-sidebar-section {
            padding: .75rem .75rem .35rem; font-size: .68rem; font-weight: 700;
            color: rgba(255,255,255,.35); letter-spacing: .08em; text-transform: uppercase;
        }
        .hb-nav-item {
            display: flex; align-items: center; gap: .7rem; width: 100%;
            padding: .65rem .9rem; margin-bottom: 4px; border-radius: 10px;
            text-decoration: none; color: rgba(255,255,255,.70);
            transition: .2s; font-size: .88rem; font-weight: 500;
        }
        .hb-nav-item:hover { background: rgba(255,255,255,.08); color: #fff; }
        .hb-nav-item.active { background: var(--hb-emerald-700); color: #fff; }
        .hb-nav-item i { width: 18px; font-size: 1rem; flex-shrink: 0; }
        .hb-sidebar-footer { flex-shrink: 0; padding: 1rem .75rem; border-top: 1px solid rgba(255,255,255,.08); }

        /* ── Main Layout ─────────────────────────────────────────────── */
        .hb-main-content {
            margin-left: 260px; width: calc(100% - 260px); min-height: 100vh;
            display: flex; flex-direction: column; background: var(--hb-gray-50); overflow-x: hidden;
        }
        .hb-topbar {
            position: sticky; top: 0; z-index: 999; background: #fff;
            border-bottom: 1px solid var(--hb-gray-200); padding: .9rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
        }
        .hb-content-area { flex: 1; width: 100%; max-width: 100%; padding: 24px; overflow-x: auto; }

        .container, .container-fluid { max-width: 100%; }
        .row { --bs-gutter-x: 1.5rem; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; }
        img { max-width: 100%; height: auto; }
        .card { max-width: 100%; }
        pre, code { white-space: pre-wrap; word-break: break-word; }

        .hb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 998; }
        .hb-overlay.show { display: block; }

        @media (max-width: 991px) {
            .hb-sidebar { transform: translateX(-100%); }
            .hb-sidebar.open { transform: translateX(0); }
            .hb-main-content { margin-left: 0; width: 100%; }
            .hb-topbar { padding: 1rem; }
            .hb-content-area { padding: 1rem; }
        }
        @media (max-width: 576px) {
            .hb-sidebar { width: 280px; }
            .hb-topbar { gap: 10px; }
            .hb-content-area { padding: 15px; }
        }
    </style>
</head>
<body>

{{-- Sidebar --}}
<div class="hb-sidebar" id="agencySidebar">
    <div class="hb-sidebar-logo">
        <div class="hb-sidebar-logo-mark">
            <i class="bi bi-heart-pulse-fill text-white" style="font-size:1rem;"></i>
        </div>
        <div class="hb-sidebar-logo-text">Healths<span>Bridge</span></div>
    </div>

    <nav class="hb-sidebar-nav">
        <div class="hb-sidebar-section">Overview</div>
        <a href="{{ route('agency.dashboard') }}" class="hb-nav-item {{ request()->routeIs('agency.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('agency.referrals.index') }}" class="hb-nav-item {{ request()->routeIs('agency.referrals.*') ? 'active' : '' }}">
            <i class="bi bi-inbox"></i> Referral Inbox
        </a>
        <a href="{{ route('agency.tours.index') }}" class="hb-nav-item {{ request()->routeIs('agency.tours.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check"></i> Tours
        </a>
        <a href="{{ route('agency.analytics.index') }}" class="hb-nav-item {{ request()->routeIs('agency.analytics.*') ? 'active' : '' }}">
            <i class="bi bi-graph-up"></i> Analytics
        </a>
        <a href="{{ route('agency.notifications.index') }}" class="hb-nav-item {{ request()->routeIs('agency.notifications.*') ? 'active' : '' }}">
            <i class="bi bi-bell"></i> Notifications
            @php $agencyUnread = auth()->user()->unreadNotifications()->count(); @endphp
            @if($agencyUnread > 0)
                <span class="hb-nav-badge">{{ $agencyUnread }}</span>
            @endif
        </a>

        <div class="hb-sidebar-section">Listing</div>
        <a href="{{ route('agency.profile.edit') }}" class="hb-nav-item {{ request()->routeIs('agency.profile.*') ? 'active' : '' }}">
            <i class="bi bi-building"></i> Profile
        </a>
        <a href="{{ route('agency.services.index') }}" class="hb-nav-item {{ request()->routeIs('agency.services.*') ? 'active' : '' }}">
            <i class="bi bi-list-check"></i> Services
        </a>
        <a href="{{ route('agency.pricing.index') }}" class="hb-nav-item {{ request()->routeIs('agency.pricing.*') ? 'active' : '' }}">
            <i class="bi bi-tags"></i> Pricing
        </a>
        <a href="{{ route('agency.hours.index') }}" class="hb-nav-item {{ request()->routeIs('agency.hours.*') ? 'active' : '' }}">
            <i class="bi bi-clock"></i> Business Hours
        </a>
        <a href="{{ route('agency.coverage.index') }}" class="hb-nav-item {{ request()->routeIs('agency.coverage.*') ? 'active' : '' }}">
            <i class="bi bi-geo-alt"></i> Coverage Areas
        </a>
        <a href="{{ route('agency.media.index') }}" class="hb-nav-item {{ request()->routeIs('agency.media.*') ? 'active' : '' }}">
            <i class="bi bi-images"></i> Media Gallery
        </a>

        <div class="hb-sidebar-section">Compliance</div>
        <a href="{{ route('agency.certifications.index') }}" class="hb-nav-item {{ request()->routeIs('agency.certifications.*') ? 'active' : '' }}">
            <i class="bi bi-patch-check"></i> Certifications
        </a>
        <a href="{{ route('agency.documents.index') }}" class="hb-nav-item {{ request()->routeIs('agency.documents.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-lock"></i> Documents
        </a>

        <div class="hb-sidebar-section">Team &amp; Billing</div>
        <a href="{{ route('agency.staff.index') }}" class="hb-nav-item {{ request()->routeIs('agency.staff.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Staff
        </a>
        <a href="{{ route('agency.billing.index') }}" class="hb-nav-item {{ request()->routeIs('agency.billing.*') ? 'active' : '' }}">
            <i class="bi bi-credit-card"></i> Billing
        </a>
        <a href="{{ route('agency.settings.index') }}" class="hb-nav-item {{ request()->routeIs('agency.settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i> Plan &amp; Settings
        </a>
    </nav>

    <div class="hb-sidebar-footer">
        <div class="d-flex align-items-center gap-2 mb-2">
            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:0.8rem;font-weight:600;color:white;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ auth()->user()->name }}
                </div>
                <div style="font-size:0.7rem;color:rgba(255,255,255,0.5);">
                    {{ str_replace('_',' ',ucfirst(auth()->user()->getRoleNames()->first() ?? '')) }}
                </div>
            </div>
        </div>
        @if(count($availableWorkspaces ?? []) > 1)
            <a href="{{ route('workspace.select') }}" class="hb-nav-item w-100" style="justify-content:flex-start;">
                <i class="bi bi-grid-3x3-gap"></i> Switch Workspace
            </a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="hb-nav-item w-100" style="border:none;background:none;cursor:pointer;justify-content:flex-start;text-align:left;">
                <i class="bi bi-box-arrow-left"></i> Sign Out
            </button>
        </form>
    </div>
</div>

<div class="hb-overlay" id="agencyOverlay" onclick="closeAgencySidebar()"></div>

{{-- Main Content --}}
<div class="hb-main-content">
    <div class="hb-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm d-lg-none" style="border:1px solid var(--hb-gray-200);border-radius:0.5rem;"
                    onclick="document.getElementById('agencySidebar').classList.toggle('open');document.getElementById('agencyOverlay').classList.toggle('show');">
                <i class="bi bi-list" style="font-size:1.1rem;"></i>
            </button>
            <div>
                <h6 class="mb-0 fw-bold" style="color:var(--hb-gray-900);font-size:0.9375rem;">
                    {{ $title ?? 'Agency Dashboard' }}
                </h6>
                @isset($breadcrumb)
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0" style="font-size:0.75rem;">{!! $breadcrumb !!}</ol>
                    </nav>
                @endisset
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;font-size:0.8rem;">
                <i class="bi bi-arrow-left me-1"></i> Back to App
            </a>
        </div>
    </div>

    <div class="hb-content-area pb-0">
        @if(session('status'))
            @php
                $statusMessages = [
                    'profile-updated'    => 'Agency profile updated successfully.',
                    'service-added'      => 'Service added successfully.',
                    'service-updated'    => 'Service updated successfully.',
                    'service-deleted'    => 'Service removed.',
                    'pricing-added'      => 'Pricing tier added successfully.',
                    'pricing-updated'    => 'Pricing tier updated successfully.',
                    'pricing-deleted'    => 'Pricing tier removed.',
                    'staff-added'        => 'Staff member added. A password setup email has been sent.',
                    'staff-removed'      => 'Staff member removed.',
                    'hours-updated'      => 'Business hours updated successfully.',
                    'coverage-added'     => 'Coverage area added successfully.',
                    'coverage-deleted'   => 'Coverage area removed.',
                    'certification-added'   => 'Certification added successfully.',
                    'certification-deleted' => 'Certification removed.',
                    'document-added'     => 'Document uploaded successfully.',
                    'document-deleted'   => 'Document removed.',
                    'media-added'        => 'Media added successfully.',
                    'media-deleted'      => 'Media removed.',
                    'featured-toggled'   => 'Featured listing setting updated.',
                    'step-saved'         => 'Progress saved.',
                    'agency-submitted'   => 'Your agency has been submitted for review. Our team will review it shortly.',
                ];
                $msg = $statusMessages[session('status')] ?? session('status');
            @endphp
            <div class="hb-alert hb-alert-success mb-3" data-auto-dismiss="5000">
                <i class="bi bi-check-circle me-2"></i> {{ $msg }}
            </div>
        @endif

        @if ($errors->any())
            <div class="hb-alert hb-alert-danger mb-3">
                <i class="bi bi-exclamation-circle me-2"></i>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="hb-content-area">
        {{ $slot }}
    </div>
</div>

<script>
    function closeAgencySidebar() {
        document.getElementById('agencySidebar').classList.remove('open');
        document.getElementById('agencyOverlay').classList.remove('show');
    }
</script>

@stack('scripts')
</body>
</html>
