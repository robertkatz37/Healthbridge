<x-family-layout title="My Tours">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Tours</li>
    @endslot

    @if($tours->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-check" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No tours scheduled yet.</p>
            </div>
        </div>
    @else
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Date</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Agency</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tours as $tour)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);" class="align-middle">
                                    <td class="px-4 py-3 fw-bold" style="color:var(--hb-gray-900);">
                                        {{ $tour->requested_date->format('M d, Y') }}
                                        @if($tour->requested_time_window) <span style="font-weight:400;color:var(--hb-gray-600);">&middot; {{ $tour->requested_time_window }}</span> @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $tour->agency->name }}</td>
                                    <td class="px-4 py-3"><span class="hb-badge-verified">{{ $tour->status->label() }}</span></td>
                                    <td class="px-4 py-3 text-end">
                                        @if(in_array($tour->status->value, ['requested', 'confirmed']))
                                            <form method="POST" action="{{ route('family.tours.cancel', $tour) }}" class="d-inline" onsubmit="return confirm('Cancel this tour?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">Cancel</button>
                                            </form>
                                        @endif
                                        @if($tour->referral)
                                            <a href="{{ route('family.referrals.show', $tour->referral) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">View</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($tours->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">{{ $tours->links() }}</div>
                @endif
            </div>
        </div>
    @endif
</x-family-layout>
