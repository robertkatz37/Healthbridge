<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} — HealthsBridge</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <style>
        /* ── Admin Shell ─────────────────────────────────────────────── */
        html, body { height: 100%; margin: 0; padding: 0; }

        .hb-shell {
            display: flex;
            min-height: 100vh;
            background: var(--hb-gray-50);
        }

        /* ── Sidebar ─────────────────────────────────────────────────── */
        .hb-sidebar {
            width: 240px;
            min-width: 240px;
            background: var(--hb-emerald-900);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            top: 0;
            overflow: hidden;
        }

        .hb-sidebar-logo {
            padding: 1.125rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 0.625rem;
            flex-shrink: 0;
        }

        .hb-sidebar-logo-mark {
            width: 34px; height: 34px;
            background: var(--hb-emerald-500);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .hb-sidebar-logo-text {
            font-family: 'Fraunces', serif;
            font-size: 1.05rem; font-weight: 700;
            color: white; line-height: 1;
        }

        .hb-sidebar-logo-text span { color: var(--hb-emerald-500); }

        .hb-sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0.5rem 0.625rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.15) transparent;
        }

        .hb-sidebar-nav::-webkit-scrollbar { width: 4px; }
        .hb-sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .hb-sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

        .hb-sidebar-section {
            padding: 0.75rem 0.625rem 0.25rem;
            font-size: 0.625rem;
            font-weight: 700;
            color: rgba(255,255,255,0.3);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .hb-nav-link {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.55rem 0.75rem;
            border-radius: 0.5rem;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 0.8375rem;
            font-weight: 500;
            transition: all 0.15s ease;
            margin-bottom: 0.1rem;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .hb-nav-link:hover {
            background: rgba(255,255,255,0.09);
            color: white;
            text-decoration: none;
        }

        .hb-nav-link.active {
            background: var(--hb-emerald-700);
            color: white;
        }

        .hb-nav-link i {
            font-size: 0.9375rem;
            width: 16px;
            flex-shrink: 0;
            line-height: 1;
        }

        .hb-nav-badge {
            margin-left: auto;
            font-size: 0.6rem;
            font-weight: 700;
            background: rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.5);
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .hb-sidebar-footer {
            padding: 0.875rem 0.75rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0;
        }

        .hb-sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 0.625rem;
        }

        .hb-sidebar-user img {
            width: 30px; height: 30px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .hb-sidebar-user-name {
            font-size: 0.775rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .hb-sidebar-user-role {
            font-size: 0.675rem;
            color: rgba(255,255,255,0.45);
        }

        /* ── Main area ────────────────────────────────────────────────── */
        .hb-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .hb-topbar {
            background: white;
            border-bottom: 1px solid var(--hb-gray-200);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .hb-page-content { padding: 1.5rem; }

        /* ── Mobile ───────────────────────────────────────────────────── */
        @media (max-width: 991px) {
            .hb-sidebar {
                position: fixed;
                top: 0; left: 0;
                z-index: 200;
                height: 100vh;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .hb-sidebar.open { transform: translateX(0); }
            .hb-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 199;
            }
            .hb-overlay.open { display: block; }
        }
    </style>
</head>
<body>
<div class="hb-shell">

    {{-- Mobile overlay --}}
    <div class="hb-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    {{-- ── Sidebar ────────────────────────────────────────────────────── --}}
    <aside class="hb-sidebar" id="adminSidebar">

        <div class="hb-sidebar-logo">
            <div class="hb-sidebar-logo-mark">
                <i class="bi bi-heart-pulse-fill" style="font-size:0.9rem;color:white;"></i>
            </div>
            <div class="hb-sidebar-logo-text">Healths<span>Bridge</span></div>
        </div>

        <nav class="hb-sidebar-nav">

            <div class="hb-sidebar-section">Overview</div>

            <a href="{{ route('admin.dashboard') }}"
               class="hb-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            @can('users.manage_all')
                <div class="hb-sidebar-section">People</div>
                <a href="{{ route('admin.users.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                </a>
            @endcan

            @can('leads.manage_all')
                <a href="{{ route('admin.advisors.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.advisors.*') ? 'active' : '' }}">
                    <i class="bi bi-briefcase"></i>
                    <span>Advisors</span>
                </a>
            @endcan

            @can('families.manage_all')
                <a href="{{ route('admin.families.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.families.*') ? 'active' : '' }}">
                    <i class="bi bi-house-heart"></i>
                    <span>Families</span>
                </a>
            @endcan

            @can('leads.manage_all')
                <a href="{{ route('admin.leads.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i>
                    <span>Leads</span>
                    @php $unassignedLeadCount = \App\Models\Lead::unassigned()->open()->count(); @endphp
                    @if($unassignedLeadCount > 0)
                        <span class="hb-nav-badge" style="background:var(--hb-warning);color:white;">{{ $unassignedLeadCount }}</span>
                    @endif
                </a>
            @endcan

            @can('manage-platform-settings')
                <a href="{{ route('admin.referrals.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.referrals.*') ? 'active' : '' }}">
                    <i class="bi bi-send"></i>
                    <span>Referrals</span>
                </a>
            @endcan

            @can('reviews.moderate')
                <a href="{{ route('admin.reviews.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                    <i class="bi bi-star"></i>
                    <span>Reviews</span>
                    @php $pendingReviewCount = \App\Models\Review::where('status', 'pending_moderation')->count(); @endphp
                    @if($pendingReviewCount > 0)
                        <span class="hb-nav-badge" style="background:var(--hb-warning);color:white;">{{ $pendingReviewCount }}</span>
                    @endif
                </a>
            @endcan

            @can('cms.manage')
                <a href="{{ route('admin.cms.pages.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.cms.pages.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Pages</span>
                </a>
                <a href="{{ route('admin.cms.blog.posts.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.cms.blog.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i>
                    <span>Blog</span>
                </a>
                <a href="{{ route('admin.cms.menus.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.cms.menus.*') ? 'active' : '' }}">
                    <i class="bi bi-list"></i>
                    <span>Menus</span>
                </a>
                <a href="{{ route('admin.cms.redirects.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.cms.redirects.*') ? 'active' : '' }}">
                    <i class="bi bi-signpost-2"></i>
                    <span>Redirects</span>
                </a>
                <a href="{{ route('admin.cms.locations.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.cms.locations.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt"></i>
                    <span>Location Guides</span>
                </a>
            @endcan

            @can('billing.manage_all')
                <div class="hb-sidebar-section">Billing</div>
                <a href="{{ route('admin.billing.invoices.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.billing.invoices.*') ? 'active' : '' }}">
                    <i class="bi bi-receipt"></i>
                    <span>Invoices</span>
                </a>
                <a href="{{ route('admin.billing.plans.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.billing.plans.*') ? 'active' : '' }}">
                    <i class="bi bi-layers"></i>
                    <span>Subscription Plans</span>
                </a>
                <a href="{{ route('admin.billing.coupons.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.billing.coupons.*') ? 'active' : '' }}">
                    <i class="bi bi-ticket-perforated"></i>
                    <span>Coupons</span>
                </a>
                <a href="{{ route('admin.billing.refunds.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.billing.refunds.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Refund Requests</span>
                    @php $pendingRefundCount = \App\Models\Refund::where('status', 'requested')->count(); @endphp
                    @if($pendingRefundCount > 0)
                        <span class="hb-nav-badge" style="background:var(--hb-warning);color:white;">{{ $pendingRefundCount }}</span>
                    @endif
                </a>
            @endcan

            @canany(['agencies.manage_all', 'agencies.moderate'])
                <div class="hb-sidebar-section">Marketplace</div>
                <a href="{{ route('admin.agencies.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.agencies.*') ? 'active' : '' }}">
                    <i class="bi bi-building"></i>
                    <span>Agencies</span>
                    @php $pendingCount = \App\Models\Agency::inModerationQueue()->count(); @endphp
                    @if($pendingCount > 0)
                        <span class="hb-nav-badge" style="background:var(--hb-warning);color:white;">{{ $pendingCount }}</span>
                    @endif
                </a>
                @can('manage-platform-settings')
                    <a href="{{ route('admin.settings.matching.edit') }}"
                       class="hb-nav-link {{ request()->routeIs('admin.settings.matching.*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Matching Engine</span>
                    </a>
                    <a href="{{ route('admin.settings.cms.edit') }}"
                       class="hb-nav-link {{ request()->routeIs('admin.settings.cms.*') ? 'active' : '' }}">
                        <i class="bi bi-gear-wide-connected"></i>
                        <span>CMS Settings</span>
                    </a>
                @endcan
            @endcanany

            @can('commissions.view')
                <div class="hb-sidebar-section">Finance</div>
                <span class="hb-nav-link" style="opacity:0.5;cursor:default;">
                    <i class="bi bi-receipt"></i>
                    <span>Invoices</span>
                    <span class="hb-nav-badge">Phase 13</span>
                </span>
                <span class="hb-nav-link" style="opacity:0.5;cursor:default;">
                    <i class="bi bi-currency-dollar"></i>
                    <span>Commissions</span>
                    <span class="hb-nav-badge">Phase 13</span>
                </span>
            @endcan

            @can('cms.manage')
                <div class="hb-sidebar-section">Content</div>
                <span class="hb-nav-link" style="opacity:0.5;cursor:default;">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>CMS Pages</span>
                    <span class="hb-nav-badge">Phase 15</span>
                </span>
                <span class="hb-nav-link" style="opacity:0.5;cursor:default;">
                    <i class="bi bi-journal-text"></i>
                    <span>Blog</span>
                    <span class="hb-nav-badge">Phase 15</span>
                </span>
            @endcan

            @can('support.manage_all_tickets')
                <div class="hb-sidebar-section">Support</div>
                <span class="hb-nav-link" style="opacity:0.5;cursor:default;">
                    <i class="bi bi-ticket-perforated"></i>
                    <span>Tickets</span>
                    <span class="hb-nav-badge">Phase 18</span>
                </span>
            @endcan

            @canany(['system.view_health', 'audit_logs.view', 'manage-platform-settings'])
                <div class="hb-sidebar-section">System</div>
                @can('system.view_health')
                    <a href="{{ route('admin.settings.system.show') }}"
                       class="hb-nav-link {{ request()->routeIs('admin.settings.system.*') ? 'active' : '' }}">
                        <i class="bi bi-activity"></i>
                        <span>System Health</span>
                    </a>
                @endcan
                @can('audit_logs.view')
                    <a href="{{ route('admin.activity-log.index') }}"
                       class="hb-nav-link {{ request()->routeIs('admin.activity-log.*') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i>
                        <span>Audit Logs</span>
                    </a>
                @endcan
                @can('manage-platform-settings')
                    <a href="{{ route('admin.reports.index') }}"
                       class="hb-nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up"></i>
                        <span>Reports & Analytics</span>
                    </a>
                @endcan
            @endcanany

            @can('manage-platform-settings')
                <div class="hb-sidebar-section">Platform</div>
                <a href="{{ route('admin.settings.index') }}"
                   class="hb-nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            @endcan

        </nav>

        <div class="hb-sidebar-footer">
            <div class="hb-sidebar-user">
                <img src="{{ auth()->user()->avatar_url }}" alt="Avatar">
                <div style="flex:1;min-width:0;">
                    <div class="hb-sidebar-user-name">{{ auth()->user()->name }}</div>
                    <div class="hb-sidebar-user-role">
                        {{ str_replace('_', ' ', ucfirst(auth()->user()->getRoleNames()->first() ?? '')) }}
                    </div>
                </div>
            </div>
            @if(count($availableWorkspaces ?? []) > 1)
                <a href="{{ route('workspace.select') }}" class="hb-nav-link">
                    <i class="bi bi-grid-3x3-gap"></i>
                    <span>Switch Workspace</span>
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="hb-nav-link" style="color:rgba(255,255,255,0.5);">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>

    </aside>

    {{-- ── Main Content ────────────────────────────────────────────────── --}}
    <div class="hb-main">

        {{-- Topbar --}}
        <div class="hb-topbar">
            <div class="d-flex align-items-center gap-3">
                {{-- Mobile toggle --}}
                <button class="btn btn-sm d-lg-none" onclick="openSidebar()"
                        style="border:1px solid var(--hb-gray-200);border-radius:0.5rem;padding:0.3rem 0.6rem;">
                    <i class="bi bi-list" style="font-size:1.1rem;color:var(--hb-gray-900);"></i>
                </button>
                <div>
                    <h6 class="mb-0 fw-bold" style="color:var(--hb-gray-900);font-size:0.9375rem;">
                        {{ $title ?? 'Admin Panel' }}
                    </h6>
                    @isset($breadcrumb)
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0" style="font-size:0.75rem;">
                                {!! $breadcrumb !!}
                            </ol>
                        </nav>
                    @endisset
                </div>
            </div>
            <a href="{{ route('dashboard') }}"
               class="btn btn-sm btn-outline-secondary"
               style="border-radius:0.5rem;font-size:0.8rem;">
                <i class="bi bi-arrow-left me-1"></i> Back to App
            </a>
        </div>

        {{-- Flash Messages --}}
        <div class="hb-page-content pb-0">
            @if(session('status'))
                @php
                    $msgs = [
                        'roles-updated' => 'User roles updated successfully.',
                        'user-deleted'  => 'User deleted successfully.',
                    ];
                    $msg = $msgs[session('status')] ?? session('status');
                @endphp
                <div class="hb-alert hb-alert-success mb-3" data-auto-dismiss="5000">
                    <i class="bi bi-check-circle me-2"></i> {{ $msg }}
                </div>
            @endif

            @if($errors->any())
                <div class="hb-alert hb-alert-danger mb-3">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Page Slot --}}
        <div class="hb-page-content">
            {{ $slot }}
        </div>

    </div>
</div>

<script>
    function openSidebar() {
        document.getElementById('adminSidebar').classList.add('open');
        document.getElementById('sidebarOverlay').classList.add('open');
    }
    function closeSidebar() {
        document.getElementById('adminSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('open');
    }
</script>

@stack('scripts')
</body>
</html>
