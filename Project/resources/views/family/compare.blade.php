<x-family-layout title="Compare Agencies">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('family.favorites.index') }}" class="hb-link">Saved Agencies</a></li>
        <li class="breadcrumb-item active">Compare</li>
    @endslot

    <div class="table-responsive">
        <table class="table" style="border-radius:1rem;overflow:hidden;font-size:0.875rem;">
            <thead>
                <tr>
                    <th style="background:var(--hb-gray-50);width:180px;">&nbsp;</th>
                    @foreach($agencies as $agency)
                        <th style="background:white;border-bottom:2px solid var(--hb-gray-200);min-width:220px;">
                            <div class="fw-bold" style="color:var(--hb-gray-900);font-size:1rem;">{{ $agency->name }}</div>
                            <div style="font-size:0.775rem;color:var(--hb-gray-600);font-weight:400;">{{ $agency->city }}, {{ $agency->state }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr style="background:var(--hb-gray-50);">
                    <td class="fw-bold" style="color:var(--hb-gray-600);">Category</td>
                    @foreach($agencies as $agency)
                        <td>{{ $agency->category?->name ?? '—' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="fw-bold" style="color:var(--hb-gray-600);">Monthly Cost</td>
                    @foreach($agencies as $agency)
                        <td>
                            @if($agency->min_monthly_cost)
                                ${{ number_format($agency->min_monthly_cost, 0) }} – ${{ number_format($agency->max_monthly_cost, 0) }}
                            @else
                                Contact for pricing
                            @endif
                        </td>
                    @endforeach
                </tr>
                <tr style="background:var(--hb-gray-50);">
                    <td class="fw-bold" style="color:var(--hb-gray-600);">Rating</td>
                    @foreach($agencies as $agency)
                        <td>
                            @if($agency->review_score)
                                <i class="bi bi-star-fill" style="color:var(--hb-gold-500);"></i> {{ number_format($agency->review_score, 1) }}
                            @else
                                No reviews yet
                            @endif
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <td class="fw-bold align-top" style="color:var(--hb-gray-600);">Services</td>
                    @foreach($agencies as $agency)
                        <td class="align-top">
                            @forelse($agency->services as $service)
                                <div style="font-size:0.8rem;"><i class="bi bi-check2 me-1" style="color:var(--hb-emerald-700);"></i>{{ $service->name }}</div>
                            @empty
                                <span style="color:var(--hb-gray-600);">—</span>
                            @endforelse
                        </td>
                    @endforeach
                </tr>
                <tr style="background:var(--hb-gray-50);">
                    <td class="fw-bold align-top" style="color:var(--hb-gray-600);">Certifications</td>
                    @foreach($agencies as $agency)
                        <td class="align-top">
                            @forelse($agency->certifications as $cert)
                                <div style="font-size:0.8rem;"><i class="bi bi-patch-check-fill me-1" style="color:var(--hb-emerald-700);"></i>{{ $cert->name }}</div>
                            @empty
                                <span style="color:var(--hb-gray-600);">—</span>
                            @endforelse
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <td class="fw-bold" style="color:var(--hb-gray-600);">&nbsp;</td>
                    @foreach($agencies as $agency)
                        <td>
                            <a href="{{ route('agencies.show', $agency) }}" class="btn btn-sm btn-primary" style="border-radius:0.5rem;">
                                View Full Profile
                            </a>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</x-family-layout>
