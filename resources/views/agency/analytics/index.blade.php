<x-agency-layout title="Analytics">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Analytics</li>
    @endslot

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="fw-bold" style="font-size:1.6rem;color:var(--hb-gray-900);">{{ $stats['favorites_count'] }}</div>
                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">Families Saved You</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="fw-bold" style="font-size:1.6rem;color:var(--hb-gray-900);">{{ $stats['times_shortlisted_by_families'] }}</div>
                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">Times Shortlisted</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="fw-bold" style="font-size:1.6rem;color:var(--hb-gray-900);">{{ $stats['review_score'] ? number_format((float) $stats['review_score'], 1) : '—' }}</div>
                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">Review Rating</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="fw-bold" style="font-size:1.6rem;color:var(--hb-gray-900);">{{ $stats['total_referrals'] }}</div>
                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">Total Referrals</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Referrals by Status</h6>
            @forelse($stats['referrals_by_status'] as $status => $count)
                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                    <span style="font-size:0.875rem;color:var(--hb-gray-900);">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    <span class="fw-bold" style="color:var(--hb-emerald-700);">{{ $count }}</span>
                </div>
            @empty
                <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No referrals yet.</p>
            @endforelse
        </div>
    </div>
</x-agency-layout>
