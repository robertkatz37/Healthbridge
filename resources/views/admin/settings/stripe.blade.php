<x-admin-layout title="Stripe Settings">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Stripe Settings</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Settings saved.</div>
    @endif
    @if($errors->any())
        <div class="hb-alert hb-alert-danger mb-4">{{ $errors->first() }}</div>
    @endif

    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3">
                @if($envConfigured)
                    <i class="bi bi-check-circle-fill" style="font-size:1.5rem;color:var(--hb-emerald-500);"></i>
                    <div>
                        <div class="fw-bold" style="color:var(--hb-gray-900);">Stripe is connected</div>
                        <div style="font-size:0.85rem;color:var(--hb-gray-600);">Running in <strong>{{ ucfirst($mode) }}</strong> mode.</div>
                    </div>
                @else
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:1.5rem;color:var(--hb-warning);"></i>
                    <div>
                        <div class="fw-bold" style="color:var(--hb-gray-900);">Stripe is not configured</div>
                        <div style="font-size:0.85rem;color:var(--hb-gray-600);">No STRIPE_SECRET is set in the environment — checkout and billing actions will fail.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">API Keys</h6>
            <p style="font-size:0.85rem;color:var(--hb-gray-600);">
                The values below are stored for reference; the application actually reads its Stripe credentials from
                <code>STRIPE_KEY</code> / <code>STRIPE_SECRET</code> / <code>STRIPE_WEBHOOK_SECRET</code> in your server's
                environment configuration, same as every other credential in this app. Update those directly for the change
                to take effect immediately; this form's values are the reference copy your deploy process should sync from.
            </p>

            <form method="POST" action="{{ route('admin.settings.stripe.update') }}">
                @csrf @method('PUT')

                <label class="hb-form-label">Publishable Key</label>
                <input type="text" name="stripe_key" class="hb-form-control mb-3" value="{{ old('stripe_key', $values['stripe_key'] ?? '') }}" placeholder="pk_test_...">

                <label class="hb-form-label">Secret Key</label>
                <input type="password" name="stripe_secret" class="hb-form-control mb-2" placeholder="{{ ($values['stripe_secret'] ?? null) ? '••••••••••••••••' : 'sk_test_...' }}">
                <small style="font-size:0.75rem;color:var(--hb-gray-600);">Leave blank to keep the current value. Stored encrypted.</small>

                <label class="hb-form-label mt-3">Webhook Signing Secret</label>
                <input type="password" name="stripe_webhook_secret" class="hb-form-control mb-2" placeholder="{{ ($values['stripe_webhook_secret'] ?? null) ? '••••••••••••••••' : 'whsec_...' }}">
                <small style="font-size:0.75rem;color:var(--hb-gray-600);">Leave blank to keep the current value. Stored encrypted.</small>

                <button type="submit" class="btn btn-primary mt-4" style="border-radius:0.625rem;">Save Settings</button>
            </form>
        </div>
    </div>
</x-admin-layout>
