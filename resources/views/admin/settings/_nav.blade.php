<div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
    <div class="card-body p-2">
        <div class="d-flex flex-wrap gap-1">
            @php
                $tabs = [
                    ['route' => 'admin.settings.general.edit', 'match' => 'admin.settings.general.*', 'icon' => 'bi-sliders', 'label' => 'General'],
                    ['route' => 'admin.settings.mail.edit', 'match' => 'admin.settings.mail.*', 'icon' => 'bi-envelope-at', 'label' => 'SMTP / Email'],
                    ['route' => 'admin.settings.branding.edit', 'match' => 'admin.settings.branding.*', 'icon' => 'bi-palette', 'label' => 'Branding'],
                    ['route' => 'admin.settings.templates.index', 'match' => 'admin.settings.templates.*', 'icon' => 'bi-file-earmark-text', 'label' => 'Email Templates'],
                    ['route' => 'admin.settings.email-logs.index', 'match' => 'admin.settings.email-logs.*', 'icon' => 'bi-list-check', 'label' => 'Email Logs'],
                    ['route' => 'admin.settings.failed-emails.index', 'match' => 'admin.settings.failed-emails.*', 'icon' => 'bi-exclamation-triangle', 'label' => 'Failed Emails'],
                    ['route' => 'admin.settings.system.show', 'match' => 'admin.settings.system.*', 'icon' => 'bi-cpu', 'label' => 'System'],
                    ['route' => 'admin.settings.matching.edit', 'match' => 'admin.settings.matching.*', 'icon' => 'bi-graph-up-arrow', 'label' => 'Matching Engine'],
                ];
                $disabledTabs = [
                    ['icon' => 'bi-shield-lock', 'label' => 'Security'],
                    ['icon' => 'bi-globe', 'label' => 'Localization'],
                    ['icon' => 'bi-hdd-stack', 'label' => 'Storage'],
                    ['icon' => 'bi-key', 'label' => 'API Keys'],
                ];
            @endphp
            @foreach($tabs as $tab)
                <a href="{{ route($tab['route']) }}"
                   class="btn btn-sm {{ request()->routeIs($tab['match']) ? 'btn-primary' : 'btn-light' }}"
                   style="border-radius:0.625rem;font-size:0.8rem;">
                    <i class="bi {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                </a>
            @endforeach
            @foreach($disabledTabs as $tab)
                <span class="btn btn-sm btn-light disabled" style="border-radius:0.625rem;font-size:0.8rem;opacity:0.5;">
                    <i class="bi {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                    <span class="hb-nav-badge" style="background:var(--hb-gray-200);color:var(--hb-gray-600);margin-left:0.35rem;">Soon</span>
                </span>
            @endforeach
        </div>
    </div>
</div>
