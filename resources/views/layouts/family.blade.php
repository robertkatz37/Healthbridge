<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Family' }} — HealthsBridge</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <style>
        html, body { margin: 0; padding: 0; width: 100%; height: 100%; overflow-x: hidden; }
        *, *::before, *::after { box-sizing: border-box; }

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
        .hb-nav-badge {
            margin-left: auto; font-size: .65rem; font-weight: 700;
            background: rgba(255,255,255,.18); color: #fff;
            padding: .1rem .45rem; border-radius: 999px;
        }
        .hb-sidebar-footer { flex-shrink: 0; padding: 1rem .75rem; border-top: 1px solid rgba(255,255,255,.08); }

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
            .hb-content-area { padding: 15px; }
        }
    </style>
</head>
<body>

<div class="hb-sidebar" id="familySidebar">
    <div class="hb-sidebar-logo">
        <div class="hb-sidebar-logo-mark">
            <i class="bi bi-heart-pulse-fill text-white" style="font-size:1rem;"></i>
        </div>
        <div class="hb-sidebar-logo-text">Healths<span>Bridge</span></div>
    </div>

    <nav class="hb-sidebar-nav">
        <div class="hb-sidebar-section">Overview</div>
        <a href="{{ route('family.dashboard') }}" class="hb-nav-item {{ request()->routeIs('family.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('family.profile.edit') }}" class="hb-nav-item {{ request()->routeIs('family.profile.*') ? 'active' : '' }}">
            <i class="bi bi-person-circle"></i> Family Profile
        </a>

        <div class="hb-sidebar-section">Care Planning</div>
        <a href="{{ route('family.care-seekers.index') }}" class="hb-nav-item {{ request()->routeIs('family.care-seekers.*') ? 'active' : '' }}">
            <i class="bi bi-person-heart"></i> Care Seekers
        </a>
        <a href="{{ route('family.needs-assessment.drafts') }}" class="hb-nav-item {{ request()->routeIs('family.needs-assessment.*') ? 'active' : '' }}">
            <i class="bi bi-clipboard2-pulse"></i> Needs Assessment
        </a>
        <a href="{{ route('family.notes.index') }}" class="hb-nav-item {{ request()->routeIs('family.notes.*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i> My Notes
        </a>

        <div class="hb-sidebar-section">Discover</div>
        <a href="{{ route('agencies.index') }}" class="hb-nav-item {{ request()->routeIs('agencies.index') ? 'active' : '' }}">
            <i class="bi bi-search"></i> Find Agencies
        </a>
        <a href="{{ route('family.favorites.index') }}" class="hb-nav-item {{ request()->routeIs('family.favorites.*') ? 'active' : '' }}">
            <i class="bi bi-bookmark-heart"></i> Saved Agencies
        </a>
        <a href="{{ route('family.shortlist.index') }}" class="hb-nav-item {{ request()->routeIs('family.shortlist.*') ? 'active' : '' }}">
            <i class="bi bi-star"></i> My Shortlist
        </a>
        <a href="{{ route('family.referrals.index') }}" class="hb-nav-item {{ request()->routeIs('family.referrals.*') ? 'active' : '' }}">
            <i class="bi bi-send"></i> Referrals
        </a>
        <a href="{{ route('family.tours.index') }}" class="hb-nav-item {{ request()->routeIs('family.tours.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check"></i> Tours
        </a>

        <div class="hb-sidebar-section">Account</div>
        <a href="{{ route('family.notifications.index') }}" class="hb-nav-item {{ request()->routeIs('family.notifications.*') ? 'active' : '' }}">
            <i class="bi bi-bell"></i> Notifications
            @php $unread = auth()->user()->unreadNotifications()->count(); @endphp
            @if($unread > 0)
                <span class="hb-nav-badge">{{ $unread }}</span>
            @endif
        </a>
        <a href="{{ route('family.activity') }}" class="hb-nav-item {{ request()->routeIs('family.activity') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i> Activity Timeline
        </a>
    </nav>

    <div class="hb-sidebar-footer">
        <div class="d-flex align-items-center gap-2 mb-2">
            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:0.8rem;font-weight:600;color:white;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ auth()->user()->name }}
                </div>
                <div style="font-size:0.7rem;color:rgba(255,255,255,0.5);">Family</div>
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

<div class="hb-overlay" id="familyOverlay" onclick="closeFamilySidebar()"></div>

<div class="hb-main-content">
    <div class="hb-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm d-lg-none" style="border:1px solid var(--hb-gray-200);border-radius:0.5rem;"
                    onclick="document.getElementById('familySidebar').classList.toggle('open');document.getElementById('familyOverlay').classList.toggle('show');">
                <i class="bi bi-list" style="font-size:1.1rem;"></i>
            </button>
            <div>
                <h6 class="mb-0 fw-bold" style="color:var(--hb-gray-900);font-size:0.9375rem;">
                    {{ $title ?? 'Family Dashboard' }}
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
                    'profile-updated' => 'Profile updated successfully.',
                    'care-seeker-created' => 'Care seeker profile created.',
                    'care-seeker-updated' => 'Care seeker profile updated.',
                    'care-seeker-deleted' => 'Care seeker profile removed.',
                    'photo-updated' => 'Photo updated successfully.',
                    'photo-removed' => 'Photo removed.',
                    'document-added' => 'Document uploaded successfully.',
                    'document-deleted' => 'Document removed.',
                    'step-saved' => 'Progress saved.',
                    'assessment-completed' => 'Needs assessment completed! Your profile has been updated.',
                    'favorite-added' => 'Agency saved to your favorites.',
                    'favorite-removed' => 'Agency removed from favorites.',
                    'note-added' => 'Note added.',
                    'note-deleted' => 'Note removed.',
                    'notifications-marked-read' => 'All notifications marked as read.',
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
    function closeFamilySidebar() {
        document.getElementById('familySidebar').classList.remove('open');
        document.getElementById('familyOverlay').classList.remove('show');
    }
</script>

@stack('scripts')
</body>
</html>
