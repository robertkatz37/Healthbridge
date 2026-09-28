<x-agency-layout title="Lead Inbox">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Lead Inbox</li>
    @endslot

    {{-- Status Filter --}}
    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('agency.leads.index') }}" class="d-flex align-items-end gap-2 flex-wrap">
                <div>
                    <label class="hb-form-label">Filter by status</label>
                    <select name="status" class="hb-form-control" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach(['pending', 'accepted', 'tour_scheduled', 'visited', 'converted', 'cancelled', 'rejected'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(request('status'))
                    <a href="{{ route('agency.leads.index') }}" class="btn btn-outline-secondary" style="border-radius:0.625rem;">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-header py-3 px-4" style="background:none;border-bottom:1px solid var(--hb-gray-200);">
            <h6 class="mb-0 fw-bold" style="color:var(--hb-gray-900);">
                Leads
                <span style="font-size:0.75rem;font-weight:400;color:var(--hb-gray-600);margin-left:0.5rem;">
                    {{ number_format($leads->total()) }} total
                </span>
            </h6>
        </div>
        <div class="card-body p-0">
            @if($leads->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">
                        No leads yet. Referrals will appear here automatically once families are matched to your agency.
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Family</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Care Seeker</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Source</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leads as $lead)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3 fw-bold" style="color:var(--hb-gray-900);">
                                        {{ $lead->family?->user?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">{{ $lead->careSeeker?->full_name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $lead->source->value)) }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusColors = [
                                                'pending' => ['bg' => 'var(--hb-gray-200)', 'text' => 'var(--hb-gray-600)'],
                                                'accepted' => ['bg' => 'var(--hb-emerald-100)', 'text' => 'var(--hb-emerald-700)'],
                                                'tour_scheduled' => ['bg' => '#EFF6FF', 'text' => '#1E40AF'],
                                                'visited' => ['bg' => '#FFFBEB', 'text' => '#92400E'],
                                                'converted' => ['bg' => 'var(--hb-emerald-100)', 'text' => 'var(--hb-emerald-700)'],
                                                'cancelled' => ['bg' => '#FEF2F2', 'text' => '#991B1B'],
                                                'rejected' => ['bg' => '#FEF2F2', 'text' => '#991B1B'],
                                            ];
                                            $c = $statusColors[$lead->status->value] ?? ['bg' => 'var(--hb-gray-200)', 'text' => 'var(--hb-gray-600)'];
                                        @endphp
                                        <span style="font-size:0.75rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;">
                                            {{ ucfirst(str_replace('_', ' ', $lead->status->value)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $lead->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($leads->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">
                        {{ $leads->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-agency-layout>
