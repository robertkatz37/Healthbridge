<x-public-layout
    :title="$seoTitle"
    :seo-description="$seoDescription"
    :canonical-url="$canonicalUrl"
    :og-image="$ogImage"
    :robots="$robots"
    :json-ld="[$localBusinessSchema, $reviewSchema, $breadcrumbSchema]"
    :breadcrumb-items="$breadcrumbs->items()"
>
    <div class="mb-3">
        <a href="{{ route('agencies.index') }}" class="hb-link" style="font-size:0.875rem;">
            <i class="bi bi-arrow-left me-1"></i>Back to directory
        </a>
    </div>

    {{-- Media Gallery Preview --}}
    @if($agency->media->isNotEmpty())
        <div class="row g-2 mb-4">
            @foreach($agency->media->where('type.value', 'photo')->take(4) as $item)
                <div class="col-6 col-md-3">
                    <img src="{{ asset('storage/' . $item->path) }}" alt="{{ $agency->name }}"
                         style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:0.75rem;">
                </div>
            @endforeach
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div>
                            <h3 class="fw-bold mb-1" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">{{ $agency->name }}</h3>
                            <span class="hb-badge-verified">{{ $agency->category?->name }}</span>
                            @if($agency->is_featured)
                                <span style="background:var(--hb-gold-500);color:white;font-size:0.7rem;font-weight:700;padding:0.2rem 0.6rem;border-radius:999px;margin-left:0.25rem;">
                                    <i class="bi bi-star-fill me-1"></i>FEATURED
                                </span>
                            @endif
                        </div>
                    </div>
                    <p style="color:var(--hb-gray-600);font-size:0.9rem;margin-top:1rem;">{{ $agency->description }}</p>
                </div>
            </div>

            @if($agency->services->isNotEmpty())
                <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Services</h6>
                        <div class="row g-2">
                            @foreach($agency->services as $service)
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center gap-2 py-1">
                                        <i class="bi bi-check-circle" style="color:var(--hb-emerald-700);"></i>
                                        <span style="font-size:0.875rem;">{{ $service->name }}</span>
                                        @if($service->price_from)
                                            <span style="font-size:0.775rem;color:var(--hb-gray-600);margin-left:auto;">from ${{ number_format($service->price_from, 0) }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if($agency->pricing->isNotEmpty())
                <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Pricing</h6>
                        <div class="row g-2">
                            @foreach($agency->pricing as $tier)
                                <div class="col-md-4">
                                    <div style="background:var(--hb-gray-50);border-radius:0.75rem;padding:1rem;text-align:center;">
                                        <div style="font-size:0.8rem;font-weight:600;color:var(--hb-gray-900);">{{ $tier->room_type }}</div>
                                        <div style="font-size:1.3rem;font-weight:700;color:var(--hb-emerald-700);">${{ number_format($tier->monthly_price, 0) }}/mo</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if($agency->certifications->isNotEmpty())
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Certifications</h6>
                        @foreach($agency->certifications as $cert)
                            <div class="d-flex align-items-center gap-2 py-1">
                                <i class="bi bi-patch-check-fill" style="color:var(--hb-emerald-700);"></i>
                                <span style="font-size:0.875rem;">{{ $cert->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);position:sticky;top:1rem;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Contact Information</h6>
                    @if($agency->phone)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-telephone" style="color:var(--hb-emerald-700);"></i>
                            <span style="font-size:0.875rem;">{{ $agency->phone }}</span>
                        </div>
                    @endif
                    @if($agency->address)
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-geo-alt" style="color:var(--hb-emerald-700);"></i>
                            <span style="font-size:0.875rem;">{{ $agency->address }}, {{ $agency->city }}, {{ $agency->state }}</span>
                        </div>
                    @endif

                    @if($agency->hours->isNotEmpty())
                        <hr style="border-color:var(--hb-gray-200);">
                        <h6 class="fw-bold mb-2" style="color:var(--hb-gray-900);font-size:0.875rem;">Business Hours</h6>
                        @php $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; @endphp
                        @foreach($agency->hours->sortBy('day_of_week') as $hour)
                            <div class="d-flex justify-content-between" style="font-size:0.8rem;padding:0.15rem 0;">
                                <span style="color:var(--hb-gray-600);">{{ $dayNames[$hour->day_of_week] }}</span>
                                <span style="color:var(--hb-gray-900);">
                                    {{ $hour->is_closed ? 'Closed' : \Illuminate\Support\Carbon::parse($hour->open_time)->format('g:i A') . ' - ' . \Illuminate\Support\Carbon::parse($hour->close_time)->format('g:i A') }}
                                </span>
                            </div>
                        @endforeach
                    @endif

                    <hr style="border-color:var(--hb-gray-200);">

                    @auth
                        @if(auth()->user()->hasRole('family'))
                            @php
                                $family = \App\Models\Family::where('user_id', auth()->id())->first();
                                $isFavorited = $family && $family->favorites()->where('agency_id', $agency->id)->exists();
                            @endphp
                            <form method="POST" action="{{ route('family.favorites.toggle', $agency) }}" class="mb-2">
                                @csrf
                                <button type="submit" class="btn w-100 {{ $isFavorited ? 'btn-outline-danger' : 'btn-outline-primary' }}" style="border-radius:0.75rem;">
                                    <i class="bi {{ $isFavorited ? 'bi-bookmark-x' : 'bi-bookmark-plus' }} me-2"></i>
                                    {{ $isFavorited ? 'Remove from Saved' : 'Save Agency' }}
                                </button>
                            </form>
                        @endif
                    @endauth

                    <button class="hb-btn-primary" disabled title="Family inquiries arrive in a later phase">
                        <i class="bi bi-envelope me-2"></i>Request Information
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Reviews Section (Phase 14) --}}
    <div class="row g-4 mt-2">
        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span style="font-size:2rem;font-weight:800;color:var(--hb-gray-900);">{{ $agency->review_score ? number_format((float) $agency->review_score, 1) : '—' }}</span>
                        <div>
                            <div style="color:var(--hb-gold-500);">
                                @for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $agency->review_score && $i <= round($agency->review_score) ? '-fill' : '' }}"></i>@endfor
                            </div>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $reviews->total() }} verified {{ Str::plural('review', $reviews->total()) }}</div>
                        </div>
                    </div>

                    @if($agencyAwards->isNotEmpty())
                        <div class="d-flex flex-wrap gap-1 mb-3">
                            @foreach($agencyAwards as $award)
                                <span class="hb-badge-verified"><i class="bi bi-trophy-fill me-1"></i>{{ ucwords(str_replace('_', ' ', $award)) }}</span>
                            @endforeach
                        </div>
                    @endif

                    @foreach($ratingBreakdown as $stars => $count)
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span style="font-size:0.75rem;width:36px;">{{ (int) $stars }} <i class="bi bi-star-fill" style="font-size:0.65rem;color:var(--hb-gold-500);"></i></span>
                            <div style="flex:1;background:var(--hb-gray-200);border-radius:999px;height:6px;overflow:hidden;">
                                <div style="background:var(--hb-gold-500);height:100%;width:{{ $ratingBreakdown->max() ? ($count / $ratingBreakdown->max()) * 100 : 0 }}%;"></div>
                            </div>
                        </div>
                    @endforeach

                    @if($categoryAverages->isNotEmpty())
                        <hr>
                        @foreach($categoryAverages as $name => $avg)
                            <div class="d-flex justify-content-between" style="font-size:0.8rem;">
                                <span style="color:var(--hb-gray-600);">{{ $name }}</span>
                                <span style="font-weight:600;">{{ $avg }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Reviews</h6>
                <form method="GET" class="d-flex gap-2">
                    <select name="reviews_sort" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="recent" {{ $sort === 'recent' ? 'selected' : '' }}>Most Recent</option>
                        <option value="highest" {{ $sort === 'highest' ? 'selected' : '' }}>Highest Rated</option>
                        <option value="lowest" {{ $sort === 'lowest' ? 'selected' : '' }}>Lowest Rated</option>
                        <option value="helpful" {{ $sort === 'helpful' ? 'selected' : '' }}>Most Helpful</option>
                    </select>
                    <select name="reviews_rating" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Ratings</option>
                        @foreach([4, 3, 2, 1] as $min)
                            <option value="{{ $min }}" {{ (int) request('reviews_rating') === $min ? 'selected' : '' }}>{{ $min }}+ stars</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if($reviews->isEmpty())
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body text-center py-5">
                        <p style="color:var(--hb-gray-600);margin:0;">No reviews yet.</p>
                    </div>
                </div>
            @else
                @foreach($reviews as $review)
                    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div style="color:var(--hb-gold-500);">
                                        @for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= round($review->overall_rating) ? '-fill' : '' }}"></i>@endfor
                                    </div>
                                    <div style="font-size:0.8rem;font-weight:600;color:var(--hb-gray-900);margin-top:0.25rem;">{{ $review->title }}</div>
                                    <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $review->display_name }} &middot; {{ $review->created_at->format('M d, Y') }}</div>
                                </div>
                                <span class="hb-badge-verified" style="font-size:0.65rem;"><i class="bi bi-patch-check-fill me-1"></i>Verified Stay</span>
                            </div>
                            <p style="font-size:0.85rem;color:var(--hb-gray-900);margin-top:0.5rem;">{{ $review->body }}</p>

                            @if($review->media->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @foreach($review->media as $media)
                                        @if($media->type === 'photo')
                                            <img src="{{ $media->url }}" style="width:80px;height:80px;object-fit:cover;border-radius:0.5rem;">
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            @if($review->reply)
                                <div style="background:var(--hb-gray-50);border-radius:0.625rem;padding:0.6rem;font-size:0.8rem;margin-top:0.5rem;">
                                    <strong><i class="bi bi-reply me-1"></i>Response from {{ $agency->name }}:</strong> {{ $review->reply->body }}
                                </div>
                            @endif

                            @auth
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary review-vote-btn" data-review-id="{{ $review->id }}" data-helpful="1" style="border-radius:0.5rem;font-size:0.75rem;">
                                        <i class="bi bi-hand-thumbs-up me-1"></i>Helpful (<span class="helpful-count">{{ $review->helpful_count }}</span>)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link text-danger" data-bs-toggle="modal" data-bs-target="#reportModal{{ $review->id }}" style="font-size:0.75rem;">
                                        <i class="bi bi-flag me-1"></i>Report
                                    </button>
                                </div>

                                <div class="modal fade" id="reportModal{{ $review->id }}" tabindex="-1">
                                    <div class="modal-dialog"><div class="modal-content">
                                        <form method="POST" action="{{ route('reviews.report', $review) }}">
                                            @csrf
                                            <div class="modal-header"><h6 class="modal-title">Report Review</h6></div>
                                            <div class="modal-body">
                                                <label class="hb-form-label">Reason</label>
                                                <select name="reason" class="hb-form-control mb-2" required>
                                                    <option value="spam">Spam</option>
                                                    <option value="offensive">Offensive</option>
                                                    <option value="fake">Fake</option>
                                                    <option value="harassment">Harassment</option>
                                                    <option value="other">Other</option>
                                                </select>
                                                <textarea name="details" class="hb-form-control" rows="2" placeholder="Additional details (optional)"></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Submit Report</button>
                                            </div>
                                        </form>
                                    </div></div>
                                </div>
                            @endauth
                        </div>
                    </div>
                @endforeach
                {{ $reviews->links() }}
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('.review-vote-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                fetch('/reviews/' + btn.dataset.reviewId + '/vote', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ is_helpful: btn.dataset.helpful === '1' }),
                }).then(r => r.json()).then(data => {
                    btn.querySelector('.helpful-count').textContent = data.helpful_count;
                });
            });
        });
    </script>
    @endpush
</x-public-layout>
