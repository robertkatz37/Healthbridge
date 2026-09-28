<x-agency-layout title="Plan & Settings">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Plan & Settings</li>
    @endslot

    {{-- Current Plan --}}
    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Current Plan</h6>
                <span class="hb-badge-verified" style="font-size:0.8rem;">{{ $currentPlan->name ?? 'No Plan' }}</span>
            </div>
            @if($currentPlan)
                <div class="row g-3">
                    @foreach($currentPlan->features as $feature)
                        <div class="col-md-4 col-6">
                            <div style="background:var(--hb-gray-50);border-radius:0.75rem;padding:0.875rem;">
                                <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;letter-spacing:0.03em;">
                                    {{ str_replace('_', ' ', $feature->feature->name) }}
                                </div>
                                <div style="font-size:0.95rem;font-weight:700;color:var(--hb-gray-900);">
                                    @if($feature->value === 'true') <i class="bi bi-check-circle" style="color:var(--hb-success);"></i> Included
                                    @elseif($feature->value === 'false') <i class="bi bi-x-circle" style="color:var(--hb-gray-600);"></i> Not Included
                                    @elseif($feature->value === 'unlimited') Unlimited
                                    @else {{ $feature->value }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Available Plans --}}
    <div class="mb-4">
        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Available Plans</h6>
        <div class="row g-3">
            @foreach($plans as $plan)
                <div class="col-md-3 col-6">
                    <div class="card h-100 {{ $currentPlan?->id === $plan->id ? '' : '' }}"
                         style="border-radius:1rem;border:{{ $currentPlan?->id === $plan->id ? '2px solid var(--hb-emerald-700)' : 'none' }};box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-3 text-center">
                            <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $plan->name }}</div>
                            <div style="font-size:1.4rem;font-weight:700;color:var(--hb-emerald-700);margin:0.5rem 0;">
                                ${{ number_format($plan->price_monthly, 0) }}<span style="font-size:0.75rem;color:var(--hb-gray-600);">/mo</span>
                            </div>
                            @if($currentPlan?->id === $plan->id)
                                <span class="hb-badge-verified">Current Plan</span>
                            @else
                                <button class="btn btn-sm btn-outline-primary w-100" style="border-radius:0.625rem;" disabled title="Billing arrives in a later phase">
                                    Upgrade
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <p style="font-size:0.775rem;color:var(--hb-gray-600);margin-top:0.75rem;">
            <i class="bi bi-info-circle me-1"></i> Plan upgrades via Stripe billing will be available in a future update. Contact support to change your plan manually.
        </p>
    </div>

    {{-- Featured Listing --}}
    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold mb-1" style="color:var(--hb-gray-900);">
                        <i class="bi bi-star-fill me-2" style="color:var(--hb-gold-500);"></i>Featured Listing
                    </h6>
                    <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;max-width:480px;">
                        Featured listings appear at the top of search results and city pages.
                        @if(!$canFeature)
                            Available on the Professional plan and above.
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('agency.settings.featured') }}">
                    @csrf
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" style="width:2.5rem;height:1.4rem;"
                               {{ $agency->is_featured ? 'checked' : '' }} {{ !$canFeature ? 'disabled' : '' }}
                               onchange="this.form.submit()">
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-agency-layout>
