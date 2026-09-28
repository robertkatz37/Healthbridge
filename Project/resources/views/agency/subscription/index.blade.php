<x-agency-layout title="Subscription">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Subscription</li>
    @endslot

    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Current Plan</h6>
            @if($subscription)
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div style="font-size:1.3rem;font-weight:700;color:var(--hb-emerald-700);">{{ $subscription->plan?->name ?? 'Free' }}</div>
                        <div style="font-size:0.8rem;color:var(--hb-gray-600);">
                            Status: <span style="font-weight:600;">{{ ucfirst($subscription->stripe_status) }}</span>
                            @if($subscription->plan?->price_monthly)
                                &middot; ${{ number_format((float) $subscription->plan->price_monthly, 2) }}/mo
                            @else
                                &middot; Free
                            @endif
                        </div>
                    </div>
                </div>

                @if($subscription->plan?->features->isNotEmpty())
                    <hr style="border-color:var(--hb-gray-200);">
                    <h6 style="font-size:0.8rem;font-weight:700;color:var(--hb-gray-600);text-transform:uppercase;margin-bottom:0.75rem;">Included Features</h6>
                    <div class="row g-2">
                        @foreach($subscription->plan->features as $feature)
                            <div class="col-md-6">
                                <div style="font-size:0.85rem;color:var(--hb-gray-900);">
                                    <i class="bi bi-check-circle-fill me-1" style="color:var(--hb-emerald-700);"></i>{{ $feature->name }}
                                    @if($feature->pivot?->value ?? null) — {{ $feature->pivot->value }} @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No subscription on file.</p>
            @endif
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-1" style="color:var(--hb-gray-900);">Available Plans</h6>
            <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                Online plan upgrades are not yet available — billing integration is planned for a future phase. Contact support to change your plan today.
            </p>
            <div class="row g-3">
                @foreach($allPlans as $plan)
                    <div class="col-md-4">
                        <div class="card h-100" style="border-radius:0.875rem;border:1px solid {{ $subscription?->plan_id === $plan->id ? 'var(--hb-emerald-700)' : 'var(--hb-gray-200)' }};">
                            <div class="card-body p-3">
                                <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $plan->name }}</div>
                                <div style="font-size:1.1rem;font-weight:700;color:var(--hb-emerald-700);">
                                    {{ $plan->price_monthly ? '$' . number_format((float) $plan->price_monthly, 2) . '/mo' : 'Free' }}
                                </div>
                                @if($subscription?->plan_id === $plan->id)
                                    <span class="hb-badge-verified mt-2 d-inline-block">Current Plan</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-agency-layout>
