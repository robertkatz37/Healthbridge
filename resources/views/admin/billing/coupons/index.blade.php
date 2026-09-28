<x-admin-layout title="Coupons">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Coupons</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif
    @if($errors->any())
        <div class="hb-alert hb-alert-danger mb-4">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Create Coupon</h6>
                    <form method="POST" action="{{ route('admin.billing.coupons.store') }}">
                        @csrf
                        <label class="hb-form-label">Code</label>
                        <input type="text" name="code" class="hb-form-control mb-2" placeholder="SAVE20" required style="text-transform:uppercase;">

                        <label class="hb-form-label">Type</label>
                        <select name="type" class="hb-form-control mb-2">
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>

                        <label class="hb-form-label">Value</label>
                        <input type="number" step="0.01" name="value" class="hb-form-control mb-2" placeholder="20" required>

                        <label class="hb-form-label">Applies To</label>
                        <select name="applies_to" class="hb-form-control mb-2">
                            <option value="all">All Purchases</option>
                            <option value="subscription">Subscriptions Only</option>
                            <option value="featured_listing">Featured Listing Only</option>
                        </select>

                        <label class="hb-form-label">Restrict to Plan (optional)</label>
                        <select name="plan_id" class="hb-form-control mb-2">
                            <option value="">— Any Plan —</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                            @endforeach
                        </select>

                        <label class="hb-form-label">Max Uses (optional)</label>
                        <input type="number" name="max_uses" class="hb-form-control mb-2" placeholder="Unlimited">

                        <label class="hb-form-label">Expires At (optional)</label>
                        <input type="datetime-local" name="expires_at" class="hb-form-control mb-3">

                        <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">Create Coupon</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-0">
                    @if($coupons->isEmpty())
                        <div class="text-center py-5"><p style="color:var(--hb-gray-600);margin:0;">No coupons yet.</p></div>
                    @else
                        <table class="table mb-0" style="font-size:0.875rem;">
                            <thead style="background:var(--hb-gray-50);">
                                <tr>
                                    <th class="px-3 py-2">Code</th>
                                    <th class="px-3 py-2">Discount</th>
                                    <th class="px-3 py-2">Applies To</th>
                                    <th class="px-3 py-2">Uses</th>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($coupons as $coupon)
                                    <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                        <td class="px-3 py-2 fw-bold">{{ $coupon->code }}</td>
                                        <td class="px-3 py-2">{{ $coupon->type->value === 'percentage' ? $coupon->value . '%' : '$' . number_format($coupon->value, 2) }}</td>
                                        <td class="px-3 py-2">{{ $coupon->applies_to->label() }}{{ $coupon->plan ? ' (' . $coupon->plan->name . ')' : '' }}</td>
                                        <td class="px-3 py-2">{{ $coupon->times_used }}{{ $coupon->max_uses ? ' / ' . $coupon->max_uses : '' }}</td>
                                        <td class="px-3 py-2">
                                            <span class="hb-badge-verified" style="{{ $coupon->is_active ? '' : 'background:#F3F4F6;color:#374151;' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-end">
                                            <form method="POST" action="{{ route('admin.billing.coupons.update', $coupon) }}" class="d-inline">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="is_active" value="{{ $coupon->is_active ? '0' : '1' }}">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">{{ $coupon->is_active ? 'Deactivate' : 'Activate' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.billing.coupons.destroy', $coupon) }}" class="d-inline" onsubmit="return confirm('Delete this coupon?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="px-3 py-3">{{ $coupons->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
