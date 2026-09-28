<x-family-layout title="Recommended Agencies">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('family.care-seekers.edit', $careSeeker) }}" class="hb-link">{{ $careSeeker->full_name }}</a></li>
        <li class="breadcrumb-item active">Recommendations</li>
    @endslot

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Recommended for {{ $careSeeker->full_name }}</h6>
            <span style="font-size:0.8rem;color:var(--hb-gray-600);">{{ $results->count() }} {{ Str::plural('agency', $results->count()) }} matched</span>
        </div>
        <form method="POST" action="{{ route('family.care-seekers.recommendations.regenerate', $careSeeker) }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm" style="border-radius:0.625rem;">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh Recommendations
            </button>
        </form>
    </div>

    {{-- Sort & Filter --}}
    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('family.care-seekers.recommendations.index', $careSeeker) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="hb-form-label">Sort by</label>
                    <select name="sort" class="hb-form-control" onchange="this.form.submit()">
                        <option value="score" {{ $sort === 'score' ? 'selected' : '' }}>Match Score</option>
                        <option value="distance" {{ $sort === 'distance' ? 'selected' : '' }}>Distance</option>
                        <option value="cost" {{ $sort === 'cost' ? 'selected' : '' }}>Cost</option>
                        <option value="rating" {{ $sort === 'rating' ? 'selected' : '' }}>Rating</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="hb-form-label">Care Type</label>
                    <select name="care_type" class="hb-form-control" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach(\App\Enums\CareType::cases() as $type)
                            <option value="{{ $type->value }}" {{ request('care_type') === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="hb-form-label">Min. Match Score</label>
                    <select name="min_score" class="hb-form-control" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach([50, 60, 70, 80, 90] as $threshold)
                            <option value="{{ $threshold }}" {{ (int) request('min_score') === $threshold ? 'selected' : '' }}>{{ $threshold }}%+</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="hb-form-label">Min. Rating</label>
                    <select name="min_rating" class="hb-form-control" onchange="this.form.submit()">
                        <option value="">Any</option>
                        @foreach([3, 3.5, 4, 4.5] as $threshold)
                            <option value="{{ $threshold }}" {{ (float) request('min_rating') === $threshold ? 'selected' : '' }}>{{ $threshold }}+ stars</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="hb-form-label">Max Monthly Cost</label>
                    <input type="number" name="max_cost" value="{{ request('max_cost') }}" class="hb-form-control" placeholder="$">
                </div>
                <div class="col-md-3">
                    <label class="hb-form-label">Max Distance (mi)</label>
                    <input type="number" name="max_distance" value="{{ request('max_distance') }}" class="hb-form-control" placeholder="miles">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">Apply Filters</button>
                </div>
                <div class="col-md-1">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="show_all" value="1" id="showAll" {{ request('show_all') ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label" for="showAll" style="font-size:0.7rem;">Show all</label>
                    </div>
                </div>
            </form>
            @if(request()->anyFilled(['care_type', 'max_cost', 'min_score', 'min_rating', 'max_distance']))
                <a href="{{ route('family.care-seekers.recommendations.index', $careSeeker) }}" class="hb-link d-inline-block mt-2" style="font-size:0.8rem;">
                    <i class="bi bi-x-lg me-1"></i>Clear filters
                </a>
            @endif
        </div>
    </div>

    @if($results->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-search" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No matching agencies found. Try adjusting your filters.</p>
            </div>
        </div>
    @else
        <div class="row g-3" id="resultsGrid">
            @foreach($results as $result)
                @php
                    $agency = $result->agency;
                    $explanation = $explanations[$result->id];
                    $tierColors = ['Excellent Match' => '#047857', 'Strong Match' => '#059669', 'Good Match' => '#D97706', 'Fair Match' => '#DC2626', 'Limited Match' => '#6B7280'];
                    $tierColor = $tierColors[$explanation['tier']] ?? '#6B7280';
                    $isFavorited = $favoriteAgencyIds->contains($agency->id);
                @endphp
                <div class="col-lg-6">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <a href="{{ route('agencies.show', $agency) }}" class="text-decoration-none">
                                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">{{ $agency->name }}</h6>
                                    </a>
                                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">
                                        <i class="bi bi-geo-alt me-1"></i>{{ $agency->city }}, {{ $agency->state }}
                                        @if($result->score_breakdown['distance_miles'] ?? null)
                                            &middot; {{ $result->score_breakdown['distance_miles'] }} miles away
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div style="font-size:1.5rem;font-weight:800;color:{{ $tierColor }};line-height:1;">{{ round($result->compatibility_score) }}%</div>
                                    <div style="font-size:0.7rem;font-weight:600;color:{{ $tierColor }};">{{ $explanation['tier'] }}</div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="hb-badge-verified">{{ $agency->category?->name }}</span>
                                @if($agency->review_score)
                                    <span style="font-size:0.7rem;font-weight:600;background:#FFFBEB;color:#92400E;padding:0.2rem 0.6rem;border-radius:999px;">
                                        <i class="bi bi-star-fill me-1"></i>{{ number_format((float) $agency->review_score, 1) }}
                                    </span>
                                @endif
                                @if($result->is_advisor_approved)
                                    <span style="font-size:0.7rem;font-weight:600;background:var(--hb-emerald-100);color:var(--hb-emerald-700);padding:0.2rem 0.6rem;border-radius:999px;">
                                        <i class="bi bi-patch-check-fill me-1"></i>Advisor Recommended
                                    </span>
                                @endif
                            </div>

                            @if($agency->min_monthly_cost)
                                <div style="font-size:0.85rem;color:var(--hb-gray-900);margin-bottom:0.75rem;">
                                    <strong>Est. ${{ number_format((float) $agency->min_monthly_cost) }}–${{ number_format((float) $agency->max_monthly_cost) }}/mo</strong>
                                </div>
                            @endif

                            <div class="mb-2">
                                @if(!empty($explanation['reasons']))
                                    <div style="font-size:0.7rem;font-weight:700;color:var(--hb-gray-600);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.25rem;">Why This Agency Matches</div>
                                    @foreach(array_slice($explanation['reasons'], 0, 4) as $reason)
                                        <div style="font-size:0.8rem;color:var(--hb-gray-900);"><i class="bi bi-check-circle-fill me-1" style="color:var(--hb-emerald-700);"></i>{{ $reason }}</div>
                                    @endforeach
                                @endif
                                @if(!empty($explanation['missing']))
                                    <div style="font-size:0.7rem;font-weight:700;color:var(--hb-gray-600);text-transform:uppercase;letter-spacing:0.03em;margin-top:0.5rem;margin-bottom:0.25rem;">Missing Requirements</div>
                                    @foreach(array_slice($explanation['missing'], 0, 2) as $miss)
                                        <div style="font-size:0.8rem;color:var(--hb-gray-600);"><i class="bi bi-x-circle-fill me-1" style="color:var(--hb-danger);"></i>{{ $miss }}</div>
                                    @endforeach
                                @endif
                            </div>

                            <button type="button" class="btn btn-link btn-sm p-0 mb-3" data-bs-toggle="collapse" data-bs-target="#explain-{{ $result->id }}" style="font-size:0.775rem;">
                                View full match explanation <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="collapse" id="explain-{{ $result->id }}">
                                <div style="background:var(--hb-gray-50);border-radius:0.625rem;padding:0.75rem;margin-bottom:0.75rem;font-size:0.775rem;">
                                    <div style="font-weight:600;margin-bottom:0.35rem;">Confidence: {{ $explanation['confidence'] }}%</div>
                                    <div style="font-weight:700;color:var(--hb-gray-600);text-transform:uppercase;font-size:0.68rem;margin-bottom:0.2rem;">Why This Agency Matches</div>
                                    @forelse($explanation['reasons'] as $reason)
                                        <div><i class="bi bi-check-circle-fill me-1" style="color:var(--hb-emerald-700);"></i>{{ $reason }}</div>
                                    @empty
                                        <div style="color:var(--hb-gray-600);">No strong matching factors identified.</div>
                                    @endforelse
                                    <div style="font-weight:700;color:var(--hb-gray-600);text-transform:uppercase;font-size:0.68rem;margin-top:0.5rem;margin-bottom:0.2rem;">Missing Requirements</div>
                                    @forelse($explanation['missing'] as $miss)
                                        <div><i class="bi bi-x-circle-fill me-1" style="color:var(--hb-danger);"></i>{{ $miss }}</div>
                                    @empty
                                        <div style="color:var(--hb-gray-600);">No significant gaps identified.</div>
                                    @endforelse
                                </div>
                            </div>

                            {{--
                                Deliberately NOT wrapped in a shared <form> for
                                Compare selection — a prior version wrapped the
                                entire results grid in <form id="compareForm">
                                for the Compare feature, which made every
                                per-card <form> below (Save, Shortlist) an
                                illegally nested <form> inside it. Nested forms
                                are invalid HTML with undefined browser
                                behavior; in practice it caused the Save
                                button's click to submit the OUTER compare
                                form instead, producing "The agencies field is
                                required." Root-caused and fixed by making
                                Compare selection a plain, form-less checkbox
                                + JS navigation (see the sticky bar below)
                                instead of an HTML form at all.
                            --}}
                            <div class="d-flex gap-2 flex-wrap">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input compare-check" value="{{ $agency->id }}" data-name="{{ $agency->name }}" id="cmp_{{ $agency->id }}">
                                    <label class="form-check-label" for="cmp_{{ $agency->id }}" style="font-size:0.75rem;">Compare</label>
                                </div>
                                <form method="POST" action="{{ route('family.favorites.toggle', $agency) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $isFavorited ? 'btn-outline-danger' : 'btn-outline-secondary' }}" style="border-radius:0.5rem;font-size:0.75rem;">
                                        <i class="bi {{ $isFavorited ? 'bi-bookmark-x' : 'bi-bookmark-plus' }} me-1"></i>{{ $isFavorited ? 'Unsave' : 'Save' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('family.care-seekers.recommendations.shortlist', [$careSeeker, $result]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $result->is_family_shortlisted ? 'btn-primary' : 'btn-outline-primary' }}" style="border-radius:0.5rem;font-size:0.75rem;">
                                        <i class="bi bi-star{{ $result->is_family_shortlisted ? '-fill' : '' }} me-1"></i>{{ $result->is_family_shortlisted ? 'Shortlisted' : 'Add to Shortlist' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Sticky Compare bar — appears once 2+ agencies are checked, stays
             visible while scrolling, so the family never has to scroll down
             to act on their selection. --}}
        <div id="compareBar" style="display:none;position:fixed;left:50%;bottom:1.5rem;transform:translateX(-50%);z-index:1050;background:var(--hb-emerald-900);color:white;border-radius:1rem;box-shadow:0 8px 30px rgba(0,0,0,0.25);padding:0.85rem 1.25rem;min-width:320px;max-width:90vw;">
            <div class="d-flex align-items-center gap-3">
                <div style="flex:1;">
                    <div style="font-size:0.85rem;font-weight:600;" id="compareBarCount">2 agencies selected</div>
                    <div id="compareBarNames" style="font-size:0.7rem;opacity:0.75;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;"></div>
                </div>
                <button type="button" class="btn btn-sm" style="border-radius:0.625rem;background:white;color:var(--hb-emerald-900);font-weight:600;white-space:nowrap;" id="compareBarButton">
                    <i class="bi bi-columns-gap me-1"></i>Compare Now
                </button>
                <button type="button" class="btn btn-sm btn-link p-0" style="color:rgba(255,255,255,0.7);" id="compareBarClear" title="Clear selection">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        const compareBar = document.getElementById('compareBar');
        const compareBarCount = document.getElementById('compareBarCount');
        const compareBarNames = document.getElementById('compareBarNames');

        function updateCompareBar() {
            const checked = [...document.querySelectorAll('.compare-check:checked')];
            if (checked.length >= 2) {
                compareBar.style.display = 'block';
                compareBarCount.textContent = checked.length + ' agencies selected';
                compareBarNames.textContent = checked.map(el => el.dataset.name).join(', ');
            } else {
                compareBar.style.display = 'none';
            }
        }

        document.querySelectorAll('.compare-check').forEach(el => el.addEventListener('change', updateCompareBar));

        document.getElementById('compareBarButton')?.addEventListener('click', function () {
            const ids = [...document.querySelectorAll('.compare-check:checked')].map(el => el.value);
            const params = ids.map(id => 'agencies[]=' + encodeURIComponent(id)).join('&');
            window.location.href = '{{ route('family.compare') }}?' + params;
        });

        document.getElementById('compareBarClear')?.addEventListener('click', function () {
            document.querySelectorAll('.compare-check:checked').forEach(el => el.checked = false);
            updateCompareBar();
        });
    </script>
    @endpush
</x-family-layout>
