<x-advisor-layout title="Calendar">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Calendar</li>
    @endslot

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">{{ $start->format('F Y') }}</h6>
        <div class="d-flex gap-2">
            <a href="{{ route('advisor.calendar', ['month' => $start->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">
                <i class="bi bi-chevron-left"></i>
            </a>
            <a href="{{ route('advisor.calendar', ['month' => $start->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>

    @if($events->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar3" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No tasks or tours scheduled this month.</p>
            </div>
        </div>
    @else
        @foreach($events->sortKeys() as $date => $dayEvents)
            <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-3">
                    <div style="font-size:0.8rem;font-weight:700;color:var(--hb-emerald-700);margin-bottom:0.5rem;">
                        {{ \Illuminate\Support\Carbon::parse($date)->format('l, F j') }}
                    </div>
                    @foreach($dayEvents as $event)
                        <div class="d-flex align-items-center gap-2 py-1">
                            <i class="bi {{ $event['type'] === 'task' ? 'bi-check2-square' : 'bi-calendar-check' }}"
                               style="color:{{ $event['type'] === 'task' ? 'var(--hb-emerald-700)' : 'var(--hb-gold-500)' }};"></i>
                            <span style="font-size:0.875rem;{{ ($event['completed'] ?? false) ? 'text-decoration:line-through;color:var(--hb-gray-600);' : '' }}">
                                {{ $event['title'] }}
                            </span>
                            @if(isset($event['status']))
                                <span class="hb-badge-verified">{{ ucfirst($event['status']) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</x-advisor-layout>
