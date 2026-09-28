<x-family-layout title="Write a Review">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('family.reviews.index') }}" class="hb-link">Reviews</a></li>
        <li class="breadcrumb-item active">Write a Review</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);max-width:700px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-1" style="color:var(--hb-gray-900);">{{ $referral->agency->name }}</h6>
            <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:1.25rem;">
                Your review is verified — we confirm every reviewer actually completed a move-in through HealthsBridge.
            </p>

            <form method="POST" action="{{ route('family.reviews.store', $referral) }}" enctype="multipart/form-data">
                @csrf

                <label class="hb-form-label">Review Title</label>
                <input type="text" name="title" class="hb-form-control mb-3 @error('title') is-invalid @enderror" value="{{ old('title') }}" maxlength="150" required>
                @error('title') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                <label class="hb-form-label">Your Review</label>
                <textarea name="body" class="hb-form-control mb-3 @error('body') is-invalid @enderror" rows="5" minlength="20" required>{{ old('body') }}</textarea>
                @error('body') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                <label class="hb-form-label mb-2">Category Ratings</label>
                <div class="mb-3">
                    @foreach($categories as $category)
                        <div class="d-flex align-items-center justify-content-between py-1">
                            <span style="font-size:0.875rem;color:var(--hb-gray-900);">{{ $category->name }}</span>
                            <div class="star-rating" data-category="{{ $category->code }}">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star star-icon" data-value="{{ $i }}" style="font-size:1.1rem;color:var(--hb-gray-300);cursor:pointer;"></i>
                                @endfor
                                <input type="hidden" name="category_ratings[{{ $category->code }}]" class="rating-input" required>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mb-3">
                    <label class="hb-form-label mb-2">Would you recommend this agency?</label>
                    <div class="d-flex gap-2">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="would_recommend" value="1" id="recYes">
                            <label class="form-check-label" for="recYes">Yes</label>
                        </div>
                        <div class="form-check ms-3">
                            <input type="radio" class="form-check-input" name="would_recommend" value="0" id="recNo">
                            <label class="form-check-label" for="recNo">No</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="hb-form-label">Photos or Videos (optional)</label>
                    <input type="file" name="media[]" class="hb-form-control" multiple accept="image/*,video/mp4,video/quicktime">
                    <small style="font-size:0.75rem;color:var(--hb-gray-600);">Up to 6 files, 20MB each.</small>
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox" class="form-check-input" name="is_anonymous" value="1" id="isAnon">
                    <label class="form-check-label" for="isAnon" style="font-size:0.875rem;">Post this review anonymously</label>
                </div>

                <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">
                    <i class="bi bi-send me-2"></i>Submit Review
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('.star-rating').forEach(function (widget) {
            const icons = widget.querySelectorAll('.star-icon');
            const input = widget.querySelector('.rating-input');
            icons.forEach(function (icon) {
                icon.addEventListener('click', function () {
                    const value = parseInt(icon.dataset.value);
                    input.value = value;
                    icons.forEach(function (i) {
                        i.className = parseInt(i.dataset.value) <= value ? 'bi bi-star-fill star-icon' : 'bi bi-star star-icon';
                        i.style.color = parseInt(i.dataset.value) <= value ? 'var(--hb-gold-500)' : 'var(--hb-gray-300)';
                    });
                });
            });
        });
    </script>
    @endpush
</x-family-layout>
