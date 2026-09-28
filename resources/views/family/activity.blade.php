<x-family-layout title="Activity Timeline">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Activity Timeline</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            @if($activities->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-clock-history" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No activity recorded yet.</p>
                </div>
            @else
                @foreach($activities as $activity)
                    <div class="d-flex gap-3 py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                        <div style="width:32px;height:32px;background:var(--hb-emerald-100);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-check2" style="font-size:0.8rem;color:var(--hb-emerald-700);"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:0.875rem;font-weight:500;color:var(--hb-gray-900);">{{ $activity->description }}</div>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);margin-top:0.2rem;">
                                {{ \Illuminate\Support\Carbon::parse($activity->created_at)->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-family-layout>
