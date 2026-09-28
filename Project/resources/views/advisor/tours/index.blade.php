<x-advisor-layout title="Tours">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Tours</li>
    @endslot

    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('advisor.tours.index') }}" class="btn btn-sm {{ !request('filter') ? 'btn-primary' : 'btn-light' }}" style="border-radius:0.625rem;">Upcoming</a>
        <a href="{{ route('advisor.tours.index', ['filter' => 'completed']) }}" class="btn btn-sm {{ request('filter') === 'completed' ? 'btn-primary' : 'btn-light' }}" style="border-radius:0.625rem;">Completed</a>
        <a href="{{ route('advisor.tours.index', ['filter' => 'cancelled']) }}" class="btn btn-sm {{ request('filter') === 'cancelled' ? 'btn-primary' : 'btn-light' }}" style="border-radius:0.625rem;">Cancelled</a>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($tours->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-calendar-check" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No tours here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Family</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Agency</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Date</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tours as $tour)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);" class="align-middle">
                                    <td class="px-4 py-3 fw-bold" style="color:var(--hb-gray-900);">
                                        @if($tour->lead)
                                            <a href="{{ route('advisor.leads.show', $tour->lead) }}" class="hb-link">{{ $tour->lead->family_name }}</a>
                                        @else — @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $tour->agency->name ?? '—' }}</td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">
                                        {{ $tour->requested_date->format('M d, Y') }}
                                        @if($tour->requested_time_window) &middot; {{ $tour->requested_time_window }} @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('advisor.tours.status', $tour) }}" class="d-inline">
                                            @csrf @method('PUT')
                                            <select name="status" class="hb-form-control" style="font-size:0.8rem;padding:0.25rem 0.5rem;width:auto;" onchange="this.form.submit()">
                                                @foreach(['requested','confirmed','completed','cancelled'] as $status)
                                                    <option value="{{ $status }}" {{ $tour->status->value === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <form method="POST" action="{{ route('advisor.tours.destroy', $tour) }}" onsubmit="return confirm('Cancel and remove this tour?')" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($tours->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">{{ $tours->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-advisor-layout>
