<x-agency-layout title="Reviews">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Reviews</li>
    @endslot

    @if(session('status') === 'reply-saved')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i>Your response has been saved.
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Rating Breakdown</h6>
                    @forelse($ratingBreakdown as $rating => $count)
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span style="font-size:0.8rem;width:40px;">{{ number_format((float) $rating, 1) }} <i class="bi bi-star-fill" style="color:var(--hb-gold-500);font-size:0.7rem;"></i></span>
                            <div style="flex:1;background:var(--hb-gray-200);border-radius:999px;height:8px;overflow:hidden;">
                                <div style="background:var(--hb-gold-500);height:100%;width:{{ $ratingBreakdown->max() > 0 ? ($count / $ratingBreakdown->max()) * 100 : 0 }}%;"></div>
                            </div>
                            <span style="font-size:0.75rem;color:var(--hb-gray-600);width:20px;">{{ $count }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No published reviews yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-8 d-flex align-items-end">
            <div class="d-flex flex-wrap gap-2">
                @foreach(['all' => 'All', 'published' => 'Published', 'pending_response' => 'Pending Response'] as $key => $label)
                    <a href="{{ route('agency.reviews.index', ['filter' => $key]) }}"
                       class="btn btn-sm {{ $filter === $key ? 'btn-primary' : 'btn-outline-secondary' }}" style="border-radius:999px;">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if($reviews->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-chat-square-text" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No reviews in this view.</p>
            </div>
        </div>
    @else
        @foreach($reviews as $review)
            <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div style="color:var(--hb-gold-500);">
                                @for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= round($review->overall_rating) ? '-fill' : '' }}"></i>@endfor
                            </div>
                            <h6 class="fw-bold mb-0 mt-1" style="color:var(--hb-gray-900);">{{ $review->title }}</h6>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $review->display_name }} &middot; {{ $review->created_at->format('M d, Y') }}</div>
                        </div>
                        <span class="hb-badge-verified">Verified Stay</span>
                    </div>
                    <p style="font-size:0.875rem;color:var(--hb-gray-900);">{{ $review->body }}</p>

                    @if($review->reply)
                        <div style="background:var(--hb-gray-50);border-radius:0.625rem;padding:0.75rem;font-size:0.85rem;margin-bottom:0.5rem;">
                            <strong>Your response{{ $review->reply->edited_at ? ' (edited)' : '' }}:</strong> {{ $review->reply->body }}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;" data-bs-toggle="collapse" data-bs-target="#reply-{{ $review->id }}">Edit Response</button>
                    @else
                        <button type="button" class="btn btn-sm btn-primary" style="border-radius:0.5rem;" data-bs-toggle="collapse" data-bs-target="#reply-{{ $review->id }}">
                            <i class="bi bi-reply me-1"></i>Respond
                        </button>
                    @endif

                    <div class="collapse mt-2" id="reply-{{ $review->id }}">
                        <form method="POST" action="{{ route('agency.reviews.reply', $review) }}">
                            @csrf
                            <textarea name="body" class="hb-form-control mb-2" rows="3" required>{{ $review->reply?->body }}</textarea>
                            <button type="submit" class="btn btn-sm btn-primary" style="border-radius:0.5rem;">Save Response</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
        {{ $reviews->links() }}
    @endif
</x-agency-layout>
