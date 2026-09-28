<x-family-layout title="My Referrals">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Referrals</li>
    @endslot

    @if($referrals->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-send" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-3" style="color:var(--hb-gray-600);">
                    No referrals yet. Once your advisor sends a referral to an agency on your behalf, you'll see it here.
                </p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($referrals as $referral)
                @php $c = $referral->status->badgeColor(); @endphp
                <div class="col-lg-6">
                    <a href="{{ route('family.referrals.show', $referral) }}" class="text-decoration-none">
                        <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">{{ $referral->agency->name }}</h6>
                                    <span style="font-size:0.75rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;">
                                        {{ $referral->status->label() }}
                                    </span>
                                </div>
                                <div style="font-size:0.8rem;color:var(--hb-gray-600);">
                                    For {{ $referral->careSeeker?->full_name }}
                                    &middot; Advisor: {{ $referral->advisor?->user?->name }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        @if($referrals->hasPages())
            <div class="mt-3">{{ $referrals->links() }}</div>
        @endif
    @endif
</x-family-layout>
