<x-agency-layout title="Agency Dashboard">

    @if(session('status') === 'agency-submitted')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="8000">
            <i class="bi bi-check-circle me-2"></i> Your agency has been submitted for review. Our team will review it shortly.
        </div>
    @endif

    {{-- Status Banner --}}
    @if($agency->status->value === 'pending_review')
        <div class="hb-alert hb-alert-warning mb-4">
            <i class="bi bi-hourglass-split me-2"></i>
            Your listing is <strong>pending review</strong>. It is not yet visible to families.
        </div>
    @elseif($agency->status->value === 'changes_requested')
        @php $latestNote = $agency->statusHistory->firstWhere('to_status', 'changes_requested'); @endphp
        <div class="hb-alert hb-alert-warning mb-4">
            <i class="bi bi-pencil-square me-2"></i>
            <strong>Changes requested.</strong> Our team reviewed your application and needs a few updates before approval.
            @if($latestNote?->reason)
                <div class="mt-2 p-2" style="background:rgba(255,255,255,0.5);border-radius:0.5rem;font-size:0.875rem;">
                    {{ $latestNote->reason }}
                </div>
            @endif
            <form method="POST" action="{{ route('agency.register.complete') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning" style="border-radius:0.625rem;font-weight:600;">
                    <i class="bi bi-send me-1"></i>Resubmit for Review
                </button>
            </form>
        </div>
    @elseif($agency->status->value === 'rejected')
        @php $latestNote = $agency->statusHistory->firstWhere('to_status', 'rejected'); @endphp
        <div class="hb-alert hb-alert-danger mb-4">
            <i class="bi bi-x-octagon me-2"></i>
            <strong>Your application was not approved.</strong>
            @if($latestNote?->reason)
                <div class="mt-2 p-2" style="background:rgba(255,255,255,0.5);border-radius:0.5rem;font-size:0.875rem;">
                    {{ $latestNote->reason }}
                </div>
            @endif
            Please contact support if you have questions.
        </div>
    @elseif($agency->status->value === 'suspended')
        @php $latestNote = $agency->statusHistory->firstWhere('to_status', 'suspended'); @endphp
        <div class="hb-alert hb-alert-danger mb-4">
            <i class="bi bi-exclamation-octagon me-2"></i>
            Your listing has been <strong>suspended</strong>.
            @if($latestNote?->reason)
                <div class="mt-2 p-2" style="background:rgba(255,255,255,0.5);border-radius:0.5rem;font-size:0.875rem;">
                    {{ $latestNote->reason }}
                </div>
            @endif
            Please contact support to resolve this.
        </div>
    @elseif($agency->status->value === 'published')
        <div class="hb-alert hb-alert-success mb-4">
            <i class="bi bi-check-circle me-2"></i>
            Your listing is <strong>live</strong> and visible to families.
        </div>
    @endif

    {{-- Welcome Banner --}}
    <div class="card mb-4" style="border-radius:1rem;border:none;background:linear-gradient(135deg,var(--hb-emerald-900) 0%,var(--hb-emerald-700) 100%);color:white;box-shadow:0 4px 20px rgba(11,110,79,0.25);">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col">
                    <p style="font-size:0.875rem;opacity:0.8;margin-bottom:0.25rem;">{{ $planName }} Plan</p>
                    <h4 class="fw-bold mb-1" style="font-family:'Fraunces',serif;">{{ $agency->name }}</h4>
                    <div style="font-size:0.8rem;opacity:0.75;">{{ $agency->city }}, {{ $agency->state }}</div>
                </div>
                <div class="col-auto d-none d-md-block text-center">
                    <div style="font-size:2rem;font-weight:700;">{{ $summary['profile_completeness'] }}%</div>
                    <div style="font-size:0.75rem;opacity:0.75;">Profile Complete</div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        @php
            $kpis = [
                ['icon' => 'bi-inbox', 'label' => 'Total Leads', 'value' => $summary['total_leads'], 'color' => 'var(--hb-emerald-700)'],
                ['icon' => 'bi-hourglass-split', 'label' => 'Pending Leads', 'value' => $summary['pending_leads'], 'color' => 'var(--hb-warning)'],
                ['icon' => 'bi-check-circle', 'label' => 'Converted', 'value' => $summary['converted_leads'], 'color' => 'var(--hb-success)'],
                ['icon' => 'bi-star', 'label' => 'Published Reviews', 'value' => $summary['total_reviews'], 'color' => 'var(--hb-gold-500)'],
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
        {{-- Pending Actions — referrals awaiting this agency's response --}}
        <div class="col-lg-7">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Pending Actions</h6>
                        <a href="{{ route('agency.referrals.index') }}" class="hb-link" style="font-size:0.8rem;">View all</a>
                    </div>

                    @forelse($pendingReferrals as $referral)
                        <a href="{{ route('agency.referrals.show', $referral) }}" class="text-decoration-none">
                            <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                                <div>
                                    <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">
                                        {{ $referral->careSeeker?->full_name ?? 'Care Seeker' }}
                                    </div>
                                    <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $referral->sent_at?->diffForHumans() }}</div>
                                </div>
                                <span style="font-size:0.7rem;font-weight:700;color:{{ $referral->priority->color() }};text-transform:uppercase;">{{ $referral->priority->label() }} Priority</span>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size:2rem;color:var(--hb-gray-200);"></i>
                            <p class="mt-2 mb-0" style="font-size:0.875rem;color:var(--hb-gray-600);">
                                No referrals awaiting a response right now.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="col-lg-5">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('agency.profile.edit') }}" class="btn btn-outline-primary text-start" style="border-radius:0.75rem;">
                            <i class="bi bi-building me-2"></i> Edit Profile
                        </a>
                        <a href="{{ route('agency.media.index') }}" class="btn btn-outline-secondary text-start" style="border-radius:0.75rem;">
                            <i class="bi bi-images me-2"></i> Add Photos
                        </a>
                        <a href="{{ route('agency.services.index') }}" class="btn btn-outline-secondary text-start" style="border-radius:0.75rem;">
                            <i class="bi bi-list-check me-2"></i> Manage Services
                        </a>
                        <a href="{{ route('agency.settings.index') }}" class="btn btn-outline-secondary text-start" style="border-radius:0.75rem;">
                            <i class="bi bi-gear me-2"></i> Plan & Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Upcoming Tours --}}
    <div class="card mt-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Upcoming Tours</h6>
                <a href="{{ route('agency.tours.index') }}" class="hb-link" style="font-size:0.8rem;">View all</a>
            </div>
            @forelse($upcomingTours as $tour)
                <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                    <div>
                        <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $tour->careSeeker?->full_name }}</div>
                        <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $tour->requested_date->format('M d, Y') }} @if($tour->requested_time_window) &middot; {{ $tour->requested_time_window }} @endif</div>
                    </div>
                    <span class="hb-badge-verified">{{ $tour->status->label() }}</span>
                </div>
            @empty
                <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No upcoming tours scheduled.</p>
            @endforelse
        </div>
    </div>
</x-agency-layout>
