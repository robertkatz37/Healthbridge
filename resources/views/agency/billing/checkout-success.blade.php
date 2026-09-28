<x-agency-layout title="Subscription Confirmed">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('agency.billing.index') }}" class="hb-link">Billing</a></li>
        <li class="breadcrumb-item active">Checkout Complete</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-5 text-center">
            <i class="bi bi-check-circle-fill" style="font-size:3rem;color:var(--hb-emerald-500);"></i>
            <h4 class="fw-bold mt-3" style="color:var(--hb-gray-900);">Thanks — your checkout is complete</h4>
            <p style="color:var(--hb-gray-600);max-width:480px;margin:0.5rem auto 1.5rem;">
                Your subscription is being activated now. This usually takes just a moment once Stripe confirms the payment.
            </p>
            <a href="{{ route('agency.billing.index') }}" class="hb-btn-primary d-inline-block" style="width:auto;padding:0.7rem 2rem;text-decoration:none;">
                Go to Billing
            </a>
        </div>
    </div>
</x-agency-layout>
