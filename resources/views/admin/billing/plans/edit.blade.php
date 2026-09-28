<x-admin-layout title="Edit Plan — {{ $plan->name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.billing.plans.index') }}" class="hb-link">Subscription Plans</a></li>
        <li class="breadcrumb-item active">{{ $plan->name }}</li>
    @endslot

    @if($errors->any())
        <div class="hb-alert hb-alert-danger mb-4">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.billing.plans.update', $plan) }}">
        @csrf @method('PUT')
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Plan Details</h6>
                        <label class="hb-form-label">Name</label>
                        <input type="text" name="name" class="hb-form-control mb-3" value="{{ old('name', $plan->name) }}" required>

                        <label class="hb-form-label">Monthly Price ($)</label>
                        <input type="number" step="0.01" name="price_monthly" class="hb-form-control mb-3" value="{{ old('price_monthly', $plan->price_monthly) }}" required>

                        <label class="hb-form-label">Yearly Price ($)</label>
                        <input type="number" step="0.01" name="price_yearly" class="hb-form-control mb-3" value="{{ old('price_yearly', $plan->price_yearly) }}" required>

                        <label class="hb-form-label">Trial Days (blank for none)</label>
                        <input type="number" name="trial_days" class="hb-form-control mb-3" value="{{ old('trial_days', $plan->trial_days) }}">

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" id="isActive" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Active (visible to agencies)</label>
                        </div>
                    </div>
                </div>

                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Stripe Price IDs</h6>
                        <label class="hb-form-label">Monthly Price ID</label>
                        <input type="text" name="stripe_price_id_monthly" class="hb-form-control mb-3" value="{{ old('stripe_price_id_monthly', $plan->stripe_price_id_monthly) }}" placeholder="price_...">

                        <label class="hb-form-label">Yearly Price ID</label>
                        <input type="text" name="stripe_price_id_yearly" class="hb-form-control" value="{{ old('stripe_price_id_yearly', $plan->stripe_price_id_yearly) }}" placeholder="price_...">
                        <small style="font-size:0.75rem;color:var(--hb-gray-600);">Create these in your Stripe Dashboard first, then paste the IDs here.</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Feature Limits</h6>
                        @foreach($plan->features as $planFeature)
                            <label class="hb-form-label">{{ str_replace('_', ' ', ucfirst($planFeature->feature->name)) }}</label>
                            <input type="text" name="features[{{ $planFeature->subscription_feature_id }}]" class="hb-form-control mb-3" value="{{ old('features.' . $planFeature->subscription_feature_id, $planFeature->value) }}">
                        @endforeach
                        <small style="font-size:0.75rem;color:var(--hb-gray-600);">Use "true"/"false" for on-off features, "unlimited" for no cap, or a number.</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3" style="border-radius:0.625rem;">Save Plan</button>
            </div>
        </div>
    </form>
</x-admin-layout>
