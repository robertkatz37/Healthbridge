<x-family-layout title="My Reviews">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Reviews</li>
    @endslot

    @if(session('status') === 'review-submitted')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="6000">
            <i class="bi bi-check-circle me-2"></i>Your review has been submitted and is awaiting moderation.
        </div>
    @endif

    @if($pendingReferrals->isNotEmpty())
        <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);border-left:4px solid var(--hb-gold-500) !important;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-1" style="color:var(--hb-gray-900);">
                    <i class="bi bi-star me-2" style="color:var(--hb-gold-500);"></i>Pending Reviews ({{ $pendingReferrals->count() }})
                </h6>
                <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:0.75rem;">
                    You've moved in — let other families know how it went.
                </p>
                @foreach($pendingReferrals as $referral)
                    <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                        <span style="font-size:0.9rem;font-weight:600;color:var(--hb-gray-900);">{{ $referral->agency->name }}</span>
                        <a href="{{ route('family.reviews.create', $referral) }}" class="btn btn-primary btn-sm" style="border-radius:0.5rem;">
                            <i class="bi bi-pencil-square me-1"></i>Write a Review
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Review History</h6>
    @if($myReviews->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-chat-square-text" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No reviews submitted yet.</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($myReviews as $review)
                @php $c = $review->status->badgeColor(); @endphp
                <div class="col-lg-6">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">{{ $review->agency->name }}</h6>
                                <span style="font-size:0.7rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;">
                                    {{ $review->status->label() }}
                                </span>
                            </div>
                            <div style="font-size:0.85rem;color:var(--hb-gold-500);margin-bottom:0.35rem;">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star{{ $i <= round($review->overall_rating) ? '-fill' : '' }}"></i>
                                @endfor
                                <span style="color:var(--hb-gray-600);font-size:0.8rem;">{{ number_format((float) $review->overall_rating, 1) }}</span>
                            </div>
                            <div style="font-size:0.85rem;font-weight:600;color:var(--hb-gray-900);">{{ $review->title }}</div>
                            <p style="font-size:0.8rem;color:var(--hb-gray-600);">{{ Str::limit($review->body, 120) }}</p>
                            @if($review->reply)
                                <div style="background:var(--hb-gray-50);border-radius:0.625rem;padding:0.6rem;font-size:0.8rem;">
                                    <strong>Agency response:</strong> {{ Str::limit($review->reply->body, 100) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-family-layout>
