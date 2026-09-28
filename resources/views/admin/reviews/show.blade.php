<x-admin-layout title="Review — {{ $review->title }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.reviews.index') }}" class="hb-link">Reviews</a></li>
        <li class="breadcrumb-item active">{{ $review->title }}</li>
    @endslot

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color:var(--hb-gray-900);">{{ $review->title }}</h5>
                            <div style="font-size:0.85rem;color:var(--hb-gray-600);">
                                {{ $review->agency->name }} &middot; by {{ $review->display_name }} &middot; {{ $review->created_at->format('M d, Y') }}
                            </div>
                        </div>
                        @php $c = $review->status->badgeColor(); @endphp
                        <span style="font-size:0.8rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.35rem 0.85rem;border-radius:999px;">{{ $review->status->label() }}</span>
                    </div>

                    <div style="color:var(--hb-gold-500);margin-bottom:0.75rem;">
                        @for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= round($review->overall_rating) ? '-fill' : '' }}"></i>@endfor
                        <span style="color:var(--hb-gray-600);font-size:0.85rem;">{{ number_format((float) $review->overall_rating, 2) }} overall</span>
                    </div>

                    <p style="font-size:0.9rem;color:var(--hb-gray-900);">{{ $review->body }}</p>

                    @if($review->would_recommend !== null)
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">
                            <i class="bi bi-{{ $review->would_recommend ? 'hand-thumbs-up-fill' : 'hand-thumbs-down-fill' }} me-1"></i>
                            {{ $review->would_recommend ? 'Would recommend' : 'Would not recommend' }}
                        </p>
                    @endif

                    <div class="row g-2 mb-3">
                        @foreach($review->categoryRatings as $rating)
                            <div class="col-6">
                                <span style="font-size:0.8rem;color:var(--hb-gray-600);">{{ $rating->category->name }}:</span>
                                <span style="font-size:0.8rem;font-weight:600;">{{ $rating->rating }}/5</span>
                            </div>
                        @endforeach
                    </div>

                    @if($review->media->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach($review->media as $media)
                                @if($media->type === 'photo')
                                    <img src="{{ $media->url }}" style="width:100px;height:100px;object-fit:cover;border-radius:0.5rem;">
                                @else
                                    <video src="{{ $media->url }}" style="width:100px;height:100px;border-radius:0.5rem;" controls></video>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="d-flex flex-wrap gap-2 pt-3" style="border-top:1px solid var(--hb-gray-200);">
                        @if($review->status->value === 'pending_moderation')
                            <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.625rem;"><i class="bi bi-check-circle me-1"></i>Approve</button>
                            </form>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal" style="border-radius:0.625rem;"><i class="bi bi-x-circle me-1"></i>Reject</button>
                        @endif
                        @if($review->status->value === 'published')
                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hideModal" style="border-radius:0.625rem;"><i class="bi bi-eye-slash me-1"></i>Hide</button>
                            @if($review->is_featured)
                                <form method="POST" action="{{ route('admin.reviews.unfeature', $review) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" style="border-radius:0.625rem;"><i class="bi bi-star-fill me-1"></i>Remove Featured</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.reviews.feature', $review) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary btn-sm" style="border-radius:0.625rem;"><i class="bi bi-star me-1"></i>Feature</button>
                                </form>
                            @endif
                        @endif
                        @if($review->status->value === 'hidden')
                            <form method="POST" action="{{ route('admin.reviews.restore', $review) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.625rem;"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Delete this review permanently?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" style="border-radius:0.625rem;"><i class="bi bi-trash me-1"></i>Delete</button>
                        </form>
                    </div>
                </div>
            </div>

            @if($review->reply)
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2" style="color:var(--hb-gray-900);">Agency Response</h6>
                        <p style="font-size:0.875rem;color:var(--hb-gray-900);">{{ $review->reply->body }}</p>
                        @if($review->reply->revisions->isNotEmpty())
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $review->reply->revisions->count() }} prior revision(s)</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            @if($review->reports->isNotEmpty())
                <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-danger);"><i class="bi bi-flag me-2"></i>Reports ({{ $review->reports->count() }})</h6>
                        @foreach($review->reports as $report)
                            <div style="font-size:0.8rem;padding:0.5rem 0;border-bottom:1px solid var(--hb-gray-200);">
                                <strong>{{ $report->reason->label() }}</strong> by {{ $report->reporter->name }}
                                @if($report->details)<div style="color:var(--hb-gray-600);">{{ $report->details }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="{{ route('admin.reviews.reject', $review) }}">
                @csrf
                <div class="modal-header"><h6 class="modal-title">Reject Review</h6></div>
                <div class="modal-body"><label class="hb-form-label">Reason</label><textarea name="reason" class="hb-form-control" rows="3" required></textarea></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="hideModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="{{ route('admin.reviews.hide', $review) }}">
                @csrf
                <div class="modal-header"><h6 class="modal-title">Hide Review</h6></div>
                <div class="modal-body"><label class="hb-form-label">Reason</label><textarea name="reason" class="hb-form-control" rows="3" required></textarea></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Hide</button>
                </div>
            </form>
        </div></div>
    </div>
</x-admin-layout>
