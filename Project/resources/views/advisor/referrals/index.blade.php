<x-advisor-layout title="Referrals">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Referrals</li>
    @endslot

    {{-- Filter tabs, exactly matching the requested advisor view:
         Pending, Accepted, Declined, Tours Scheduled, Move-ins, Conversions --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach([
            ['key' => 'all', 'label' => 'All', 'count' => $counts['all']],
            ['key' => 'pending', 'label' => 'Pending', 'count' => $counts['pending']],
            ['key' => 'accepted', 'label' => 'Accepted', 'count' => $counts['accepted']],
            ['key' => 'declined', 'label' => 'Declined', 'count' => $counts['declined']],
            ['key' => 'tours', 'label' => 'Tours', 'count' => $counts['tours']],
            ['key' => 'move_ins', 'label' => 'Move-ins', 'count' => $counts['move_ins']],
            ['key' => 'conversions', 'label' => 'Conversions', 'count' => $counts['conversions']],
        ] as $tab)
            <a href="{{ route('advisor.referrals.index', ['filter' => $tab['key']]) }}"
               class="btn btn-sm {{ $filter === $tab['key'] ? 'btn-primary' : 'btn-outline-secondary' }}"
               style="border-radius:999px;">
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($referrals->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-send" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No referrals in this view yet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Family</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Agency</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Priority</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Sent</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($referrals as $referral)
                                @php $c = $referral->status->badgeColor(); @endphp
                                <tr style="border-bottom:1px solid var(--hb-gray-200);" class="align-middle">
                                    <td class="px-4 py-3 fw-bold" style="color:var(--hb-gray-900);">{{ $referral->family_name }}</td>
                                    <td class="px-4 py-3">{{ $referral->agency->name }}</td>
                                    <td class="px-4 py-3">
                                        <span style="font-size:0.7rem;font-weight:700;color:{{ $referral->priority->color() }};text-transform:uppercase;">{{ $referral->priority->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span style="font-size:0.75rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;">
                                            {{ $referral->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $referral->sent_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('advisor.referrals.show', $referral) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($referrals->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">{{ $referrals->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-advisor-layout>
