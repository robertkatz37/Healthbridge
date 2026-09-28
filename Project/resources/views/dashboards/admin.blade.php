<x-admin-layout title="Admin Dashboard">

    {{-- Welcome Banner --}}
    <div class="card mb-4" style="border-radius:1rem;border:none;background:linear-gradient(135deg,var(--hb-emerald-900) 0%,var(--hb-emerald-700) 100%);color:white;box-shadow:0 4px 20px rgba(11,110,79,0.25);">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col">
                    <p style="font-size:0.875rem;opacity:0.8;margin-bottom:0.25rem;">Admin Panel</p>
                    <h4 class="fw-bold mb-1" style="font-family:'Fraunces',serif;">
                        Welcome, {{ auth()->user()->name }}
                    </h4>
                    <div style="font-size:0.8rem;opacity:0.75;">
                        @foreach(auth()->user()->getRoleNames() as $role)
                            <span style="background:rgba(255,255,255,0.15);padding:0.2rem 0.6rem;border-radius:999px;font-weight:600;margin-right:0.25rem;">
                                {{ str_replace('_', ' ', ucwords($role)) }}
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="col-auto d-none d-md-block">
                    <img src="{{ auth()->user()->avatar_url }}" alt="Avatar"
                         style="width:64px;height:64px;border-radius:50%;border:3px solid rgba(255,255,255,0.2);">
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="row g-3 mb-4">
        @php
            $statCards = [
                ['icon' => 'bi-people',   'label' => 'Total Users',           'value' => number_format($stats['total_users']),    'color' => 'var(--hb-emerald-700)'],
                ['icon' => 'bi-building', 'label' => 'Total Agencies',        'value' => number_format($stats['total_agencies']), 'color' => 'var(--hb-info)'],
                ['icon' => 'bi-house',    'label' => 'Families Registered',   'value' => number_format($stats['total_families']), 'color' => 'var(--hb-gold-500)'],
                ['icon' => 'bi-clock',    'label' => 'Agencies Pending Review','value' => number_format($stats['pending_agencies']),'color' => 'var(--hb-warning)'],
            ];
        @endphp

        @foreach($statCards as $card)
            <div class="col-6 col-lg-3">
                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div style="width:44px;height:44px;background:{{ $card['color'] }}1a;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;">
                                <i class="bi {{ $card['icon'] }}" style="font-size:1.2rem;color:{{ $card['color'] }};"></i>
                            </div>
                        </div>
                        <div class="fw-bold" style="font-size:1.75rem;color:var(--hb-gray-900);line-height:1;">{{ $card['value'] }}</div>
                        <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.25rem;">{{ $card['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Quick Actions --}}
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Quick Actions</h6>
                    <div class="d-grid gap-2">
                        @can('users.manage_all')
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary text-start" style="border-radius:0.75rem;">
                                <i class="bi bi-people me-2"></i> Manage Users
                            </a>
                        @endcan
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary text-start" style="border-radius:0.75rem;">
                            <i class="bi bi-person-gear me-2"></i> My Profile
                        </a>
                        <a href="{{ route('two-factor.setup') }}" class="btn btn-outline-secondary text-start" style="border-radius:0.75rem;">
                            <i class="bi bi-shield-lock me-2"></i> Security (2FA)
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Upcoming Phases</h6>
                    @php
                        $upcoming = [
                            ['icon' => 'bi-building',      'label' => 'Agency Module',        'phase' => 7],
                            ['icon' => 'bi-house',          'label' => 'Family Module',         'phase' => 8],
                            ['icon' => 'bi-diagram-3',      'label' => 'Matching Engine',       'phase' => 10],
                            ['icon' => 'bi-link-45deg',     'label' => 'Referral Engine',       'phase' => 12],
                        ];
                    @endphp
                    @foreach($upcoming as $item)
                        <div class="d-flex align-items-center gap-3 mb-2 py-1">
                            <div style="width:34px;height:34px;background:var(--hb-emerald-100);border-radius:0.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi {{ $item['icon'] }}" style="color:var(--hb-emerald-700);font-size:0.9rem;"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $item['label'] }}</div>
                            </div>
                            <span style="font-size:0.7rem;font-weight:700;background:var(--hb-emerald-100);color:var(--hb-emerald-700);padding:0.15rem 0.5rem;border-radius:999px;">Phase {{ $item['phase'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</x-admin-layout>
