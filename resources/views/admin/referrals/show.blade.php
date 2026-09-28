<x-admin-layout title="Referral — {{ $referral->agency->name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.referrals.index') }}" class="hb-link">Referrals</a></li>
        <li class="breadcrumb-item active">{{ $referral->agency->name }}</li>
    @endslot

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold mb-1" style="color:var(--hb-gray-900);">{{ $referral->agency->name }}</h5>
                            <div style="font-size:0.85rem;color:var(--hb-gray-600);">
                                {{ $referral->family_name }} &middot; {{ $referral->careSeeker?->full_name }} &middot; Advisor: {{ $referral->advisor?->user?->name }}
                            </div>
                        </div>
                        @php $c = $referral->status->badgeColor(); @endphp
                        <span style="font-size:0.8rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.35rem 0.85rem;border-radius:999px;">
                            {{ $referral->status->label() }}
                        </span>
                    </div>
                    <dl class="row mb-0" style="font-size:0.85rem;">
                        <dt class="col-4" style="color:var(--hb-gray-600);">Priority</dt>
                        <dd class="col-8">{{ $referral->priority->label() }}</dd>
                        <dt class="col-4" style="color:var(--hb-gray-600);">Sent</dt>
                        <dd class="col-8">{{ $referral->sent_at?->format('M d, Y g:i A') ?? '—' }}</dd>
                        <dt class="col-4" style="color:var(--hb-gray-600);">Agency Responded</dt>
                        <dd class="col-8">{{ $referral->agency_responded_at?->format('M d, Y g:i A') ?? '—' }}</dd>
                        @if($referral->closed_reason)
                            <dt class="col-4" style="color:var(--hb-gray-600);">Closed Reason</dt>
                            <dd class="col-8">{{ $referral->closed_reason }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if($referral->tourRequests->isNotEmpty())
                <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Tours</h6>
                        @foreach($referral->tourRequests as $tour)
                            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                                <span>{{ $tour->requested_date->format('M d, Y') }}</span>
                                <span class="hb-badge-verified">{{ $tour->status->label() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Notes ({{ $referral->notes->count() }})</h6>
                    @forelse($referral->notes as $note)
                        <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.85rem;">
                            <span class="hb-badge-verified" style="font-size:0.65rem;">{{ ucfirst($note->author_type) }}</span>
                            {{ $note->author?->name }} &middot; {{ $note->created_at->diffForHumans() }}
                            <div style="color:var(--hb-gray-900);margin-top:0.2rem;">{{ $note->content }}</div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No notes.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-clock-history me-2"></i>Audit History</h6>
                    @forelse($referral->statusHistory as $entry)
                        <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.8rem;">
                            <div style="font-weight:600;color:var(--hb-gray-900);">{{ \App\Enums\ReferralStatus::from($entry->to_status)->label() }}</div>
                            <div style="color:var(--hb-gray-600);">{{ $entry->changedBy?->name ?? 'System' }} &middot; {{ $entry->created_at->diffForHumans() }}</div>
                            @if($entry->reason)<div style="color:var(--hb-gray-600);font-style:italic;">"{{ $entry->reason }}"</div>@endif
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">No history.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
