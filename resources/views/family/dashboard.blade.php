<x-family-layout title="Family Dashboard">

    <div class="card mb-4" style="border-radius:1rem;border:none;background:linear-gradient(135deg,var(--hb-emerald-900) 0%,var(--hb-emerald-700) 100%);color:white;box-shadow:0 4px 20px rgba(11,110,79,0.25);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width:52px;height:52px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-house-heart" style="font-size:1.4rem;color:white;"></i>
                </div>
                <div>
                    <p style="font-size:0.8rem;opacity:0.8;margin-bottom:0.1rem;">Family Dashboard</p>
                    <h4 class="fw-bold mb-0" style="font-family:'Fraunces',serif;">{{ auth()->user()->name }}</h4>
                    <div style="font-size:0.8rem;opacity:0.7;">Finding the right care for your loved one</div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        @php
            $kpis = [
                ['icon' => 'bi-person-heart', 'label' => 'Care Seekers', 'value' => $summary['care_seeker_count'], 'color' => 'var(--hb-emerald-700)'],
                ['icon' => 'bi-bookmark-heart', 'label' => 'Saved Agencies', 'value' => $summary['favorite_count'], 'color' => 'var(--hb-gold-500)'],
                ['icon' => 'bi-check2-circle', 'label' => 'Completed Assessments', 'value' => $summary['completed_assessments'], 'color' => 'var(--hb-success)'],
                ['icon' => 'bi-hourglass-split', 'label' => 'Assessments In Progress', 'value' => $summary['draft_assessments'], 'color' => 'var(--hb-warning)'],
            ];
        @endphp
        @foreach($kpis as $kpi)
            <div class="col-6 col-lg-3">
                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <div style="width:42px;height:42px;background:{{ $kpi['color'] }}1a;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;">
                            <i class="bi {{ $kpi['icon'] }}" style="font-size:1.15rem;color:{{ $kpi['color'] }};"></i>
                        </div>
                        <div class="fw-bold" style="font-size:1.6rem;color:var(--hb-gray-900);line-height:1;">{{ $kpi['value'] }}</div>
                        <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.25rem;">{{ $kpi['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        {{-- Care Seekers --}}
        <div class="col-lg-7">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Your Care Seekers</h6>
                        <a href="{{ route('family.care-seekers.create') }}" class="btn btn-sm btn-primary" style="border-radius:0.625rem;">
                            <i class="bi bi-plus-lg me-1"></i> Add Care Seeker
                        </a>
                    </div>

                    @forelse($family->careSeekers as $seeker)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $seeker->photo_url }}" alt="{{ $seeker->full_name }}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                <div>
                                    <div style="font-size:0.9rem;font-weight:600;color:var(--hb-gray-900);">{{ $seeker->full_name }}</div>
                                    <div style="font-size:0.775rem;color:var(--hb-gray-600);">
                                        @if($seeker->care_type_needed) {{ $seeker->care_type_needed->label() }} @else No care type set @endif
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('family.care-seekers.edit', $seeker) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-person-heart" style="font-size:2rem;color:var(--hb-gray-200);"></i>
                            <p class="mt-2 mb-3" style="font-size:0.875rem;color:var(--hb-gray-600);">
                                Add a care seeker profile to get started with your search.
                            </p>
                            <a href="{{ route('family.care-seekers.create') }}" class="btn btn-primary btn-sm" style="border-radius:0.625rem;">
                                Add Care Seeker
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Favorites --}}
        <div class="col-lg-5">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Saved Agencies</h6>
                        <a href="{{ route('family.favorites.index') }}" class="hb-link" style="font-size:0.8rem;">View all</a>
                    </div>

                    @forelse($recentFavorites as $favorite)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div>
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $favorite->agency->name }}</div>
                                <div style="font-size:0.775rem;color:var(--hb-gray-600);">{{ $favorite->agency->city }}, {{ $favorite->agency->state }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-bookmark-heart" style="font-size:2rem;color:var(--hb-gray-200);"></i>
                            <p class="mt-2 mb-0" style="font-size:0.875rem;color:var(--hb-gray-600);">
                                No saved agencies yet. <a href="{{ route('agencies.index') }}" class="hb-link">Browse agencies</a>
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Referral Status Cards (Phase 13) --}}
    @if($referrals->isNotEmpty())
        <div class="d-flex align-items-center justify-content-between mt-4 mb-3">
            <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Referral Status</h6>
            <a href="{{ route('family.referrals.index') }}" class="hb-link" style="font-size:0.8rem;">View all</a>
        </div>
        <div class="row g-3 mb-4">
            @foreach($referrals as $referral)
                @php $c = $referral->status->badgeColor(); @endphp
                <div class="col-md-4">
                    <a href="{{ route('family.referrals.show', $referral) }}" class="text-decoration-none">
                        <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                            <div class="card-body p-3">
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $referral->agency->name }}</div>
                                <span style="font-size:0.7rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;display:inline-block;margin-top:0.35rem;">
                                    {{ $referral->status->label() }}
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    <div class="row g-4">
        {{-- Upcoming Tours --}}
        <div class="col-lg-6">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Upcoming Tours</h6>
                        <a href="{{ route('family.tours.index') }}" class="hb-link" style="font-size:0.8rem;">View all</a>
                    </div>
                    @forelse($upcomingTours as $tour)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div>
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $tour->agency->name }}</div>
                                <div style="font-size:0.775rem;color:var(--hb-gray-600);">{{ $tour->requested_date->format('M d, Y') }} @if($tour->requested_time_window) &middot; {{ $tour->requested_time_window }} @endif</div>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No upcoming tours.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Referral Timeline --}}
        <div class="col-lg-6">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Referral Timeline</h6>
                    @forelse($referralTimeline as $entry)
                        <div class="py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.8rem;">
                            <span style="font-weight:600;color:var(--hb-gray-900);">{{ $entry->referral->agency->name }}</span>
                            <span style="color:var(--hb-gray-600);"> — {{ \App\Enums\ReferralStatus::from($entry->to_status)->label() }}</span>
                            <div style="color:var(--hb-gray-600);">{{ $entry->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No referral activity yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Advisor Messages --}}
    @if($advisorMessages->isNotEmpty())
        <div class="card mt-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Your Advisor</h6>
                    @foreach($advisorMessages as $lead)
                        <a href="{{ route('family.leads.conversation', $lead) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                            <i class="bi bi-chat-dots me-1"></i>Message {{ $lead->advisor?->user?->name }}
                        </a>
                    @endforeach
                </div>
                <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:0;">
                    {{ $advisorMessages->first()->advisor?->user?->name }} is your dedicated advisor helping coordinate referrals and tours.
                </p>
            </div>
        </div>
    @endif

    {{-- My Reviews / Pending Reviews (Phase 14) --}}
    @if($pendingReviewReferrals->isNotEmpty() || $myReviews->isNotEmpty())
        <div class="card mt-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">My Reviews</h6>
                    <a href="{{ route('family.reviews.index') }}" class="hb-link" style="font-size:0.8rem;">View all</a>
                </div>

                @if($pendingReviewReferrals->isNotEmpty())
                    <div style="font-size:0.7rem;font-weight:700;color:var(--hb-gray-600);text-transform:uppercase;margin-bottom:0.5rem;">Pending Reviews</div>
                    @foreach($pendingReviewReferrals as $referral)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <span style="font-size:0.875rem;color:var(--hb-gray-900);">{{ $referral->agency->name }}</span>
                            <a href="{{ route('family.reviews.create', $referral) }}" class="btn btn-sm btn-primary" style="border-radius:0.5rem;">Write a Review</a>
                        </div>
                    @endforeach
                @endif

                @if($myReviews->isNotEmpty())
                    <div style="font-size:0.7rem;font-weight:700;color:var(--hb-gray-600);text-transform:uppercase;margin:0.75rem 0 0.5rem;">Recent Reviews</div>
                    @foreach($myReviews as $review)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                            <span>{{ $review->agency->name }}</span>
                            <span class="hb-badge-verified">{{ $review->status->label() }}</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endif
</x-family-layout>
