<x-family-layout title="Referral — {{ $referral->agency->name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('family.referrals.index') }}" class="hb-link">Referrals</a></li>
        <li class="breadcrumb-item active">{{ $referral->agency->name }}</li>
    @endslot

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <a href="{{ route('agencies.show', $referral->agency) }}" class="text-decoration-none">
                                <h5 class="fw-bold mb-1" style="color:var(--hb-gray-900);">{{ $referral->agency->name }}</h5>
                            </a>
                            <div style="font-size:0.85rem;color:var(--hb-gray-600);">
                                <i class="bi bi-geo-alt me-1"></i>{{ $referral->agency->city }}, {{ $referral->agency->state }}
                            </div>
                        </div>
                        @php $c = $referral->status->badgeColor(); @endphp
                        <span style="font-size:0.8rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.35rem 0.85rem;border-radius:999px;">
                            {{ $referral->status->label() }}
                        </span>
                    </div>
                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">
                        Your advisor, {{ $referral->advisor?->user?->name }}, is managing this referral on your behalf.
                    </div>
                </div>
            </div>

            {{-- Scheduled tours --}}
            @if($referral->tourRequests->isNotEmpty())
                <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-calendar-check me-2"></i>Tours</h6>
                        @foreach($referral->tourRequests as $tour)
                            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                                <span>{{ $tour->requested_date->format('M d, Y') }} @if($tour->requested_time_window) &middot; {{ $tour->requested_time_window }} @endif</span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="hb-badge-verified">{{ $tour->status->label() }}</span>
                                    @if(in_array($tour->status->value, ['requested', 'confirmed']))
                                        <form method="POST" action="{{ route('family.tours.cancel', $tour) }}" onsubmit="return confirm('Cancel this tour?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">Cancel</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Advisor updates — only notes explicitly shared with the family --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Advisor Updates</h6>
                    @forelse($visibleNotes as $note)
                        <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $note->created_at->diffForHumans() }}</div>
                            <div style="font-size:0.875rem;color:var(--hb-gray-900);margin-top:0.15rem;">{{ $note->content }}</div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No updates shared yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-clock-history me-2"></i>Status Timeline</h6>
                    @forelse($referral->statusHistory as $entry)
                        <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.8rem;">
                            <div style="font-weight:600;color:var(--hb-gray-900);">
                                {{ \App\Enums\ReferralStatus::from($entry->to_status)->label() }}
                            </div>
                            <div style="color:var(--hb-gray-600);">{{ $entry->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">No history yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-family-layout>
