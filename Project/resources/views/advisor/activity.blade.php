<x-advisor-layout title="Activity Timeline">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Activity Timeline</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            @forelse($entries as $entry)
                <div class="d-flex gap-3 py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                    <div style="width:32px;height:32px;background:{{ $entry['color'] }}1a;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi {{ $entry['icon'] }}" style="font-size:0.8rem;color:{{ $entry['color'] }};"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">
                            {{ $entry['title'] }}
                            @if($entry['family_name'])
                                <a href="{{ route('advisor.leads.show', $entry['lead']) }}" class="hb-link" style="font-size:0.8rem;font-weight:400;">— {{ $entry['family_name'] }}</a>
                            @endif
                        </div>
                        @if($entry['detail'])
                            <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.2rem;">{{ Str::limit($entry['detail'], 150) }}</div>
                        @endif
                        <div style="font-size:0.75rem;color:var(--hb-gray-600);margin-top:0.2rem;">
                            @if($entry['actor']) {{ $entry['actor'] }} &middot; @endif {{ $entry['at']->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-clock-history" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No activity recorded yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-advisor-layout>
