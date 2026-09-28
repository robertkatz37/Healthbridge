<x-admin-layout title="Subscription Plans">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Subscription Plans</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif

    <div class="row g-3">
        @foreach($plans as $plan)
            <div class="col-md-6 col-lg-3">
                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">{{ $plan->name }}</h6>
                            <span class="hb-badge-verified" style="{{ $plan->is_active ? '' : 'background:#F3F4F6;color:#374151;' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <div style="font-size:1.3rem;font-weight:700;color:var(--hb-emerald-700);">${{ number_format($plan->price_monthly, 0) }}<span style="font-size:0.7rem;color:var(--hb-gray-600);">/mo</span></div>
                        <div style="font-size:0.75rem;color:var(--hb-gray-600);">${{ number_format($plan->price_yearly, 0) }}/yr &middot; {{ $plan->trial_days ? $plan->trial_days . '-day trial' : 'No trial' }}</div>
                        <div style="font-size:0.8rem;color:var(--hb-gray-900);margin-top:0.5rem;"><strong>{{ $plan->subscriptions_count }}</strong> active subscribers</div>
                        <a href="{{ route('admin.billing.plans.edit', $plan) }}" class="btn btn-outline-primary btn-sm w-100 mt-3" style="border-radius:0.5rem;">Edit Plan</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-admin-layout>
