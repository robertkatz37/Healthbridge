<x-agency-layout title="Billing & Subscription">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Billing & Subscription</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif
    @if($errors->any())
        <div class="hb-alert hb-alert-danger mb-4">{{ $errors->first() }}</div>
    @endif

    @php
        $currentPlan = $subscription?->plan;
        $isLocalPlaceholder = $subscription && str_starts_with((string) $subscription->stripe_id, 'local_');
        $isCanceling = $subscription?->ends_at !== null;
        $isTrialing = $subscription?->trial_ends_at && $subscription->trial_ends_at->isFuture();
    @endphp

    <div class="row g-4 mb-4">
        {{-- Current Plan / Billing Status --}}
        <div class="col-lg-8">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Current Plan</h6>
                        <span class="hb-badge-verified" style="font-size:0.8rem;">{{ $currentPlan?->name ?? 'No Plan' }}</span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3 col-6">
                            <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Subscription Status</div>
                            <div style="font-weight:700;color:var(--hb-gray-900);">
                                @if($isTrialing) <span style="color:#92400E;">Trialing</span>
                                @elseif($isCanceling) <span style="color:#991B1B;">Canceling</span>
                                @elseif($currentPlan?->code === 'free') <span style="color:var(--hb-gray-600);">Free</span>
                                @else <span style="color:var(--hb-emerald-700);">Active</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Billing Status</div>
                            <div style="font-weight:700;color:var(--hb-gray-900);">
                                @php
                                    $billingStatusLabels = ['active' => ['Good Standing', 'var(--hb-emerald-700)'], 'past_due' => ['Payment Due', '#991B1B'], 'incomplete' => ['Incomplete', '#92400E'], 'canceled' => ['Canceled', 'var(--hb-gray-600)']];
                                    [$billingLabel, $billingColor] = $billingStatusLabels[$subscription?->stripe_status] ?? ['N/A', 'var(--hb-gray-600)'];
                                @endphp
                                <span style="color:{{ $billingColor }};">{{ $billingLabel }}</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Next Renewal</div>
                            <div style="font-weight:700;color:var(--hb-gray-900);">
                                @if($isCanceling) Ends {{ $subscription->ends_at->format('M d, Y') }}
                                @elseif($isTrialing) Trial ends {{ $subscription->trial_ends_at->format('M d, Y') }}
                                @elseif($currentPlan && (float) $currentPlan->price_monthly > 0) {{ now()->addMonth()->format('M d, Y') }}
                                @else &mdash;
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Featured Listing</div>
                            <div style="font-weight:700;color:var(--hb-gray-900);">
                                @if($agency->is_featured)
                                    <i class="bi bi-star-fill" style="color:var(--hb-gold-500);"></i> Active
                                    @if($agency->featured_until) <span style="font-size:0.75rem;color:var(--hb-gray-600);">until {{ $agency->featured_until->format('M d') }}</span>@endif
                                @else
                                    <span style="color:var(--hb-gray-600);">Not active</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($currentPlan)
                        <div class="row g-2">
                            @foreach($currentPlan->features as $feature)
                                <div class="col-md-4 col-6">
                                    <div style="background:var(--hb-gray-50);border-radius:0.75rem;padding:0.7rem;">
                                        <div style="font-size:0.65rem;color:var(--hb-gray-600);text-transform:uppercase;">{{ str_replace('_', ' ', $feature->feature->name) }}</div>
                                        <div style="font-size:0.85rem;font-weight:700;color:var(--hb-gray-900);">
                                            @if($feature->value === 'true') <i class="bi bi-check-circle" style="color:var(--hb-success);"></i>
                                            @elseif($feature->value === 'false') <i class="bi bi-x-circle" style="color:var(--hb-gray-600);"></i>
                                            @elseif($feature->value === 'unlimited') Unlimited
                                            @else {{ $feature->value }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @if($isCanceling)
                            <form method="POST" action="{{ route('agency.billing.resume') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.625rem;">Resume Subscription</button>
                            </form>
                        @elseif(!$isLocalPlaceholder && $currentPlan && (float) $currentPlan->price_monthly > 0)
                            <form method="POST" action="{{ route('agency.billing.cancel') }}" onsubmit="return confirm('Cancel your subscription at the end of the current period?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm" style="border-radius:0.625rem;">Cancel Subscription</button>
                            </form>
                        @endif
                        @if(!$canFeature && !$agency->is_featured)
                            <form method="POST" action="{{ route('agency.billing.featured-listing.purchase') }}" onsubmit="return confirm('Purchase Featured Listing for $49 (30 days)?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm" style="border-radius:0.625rem;"><i class="bi bi-star me-1"></i>Purchase Featured Listing — $49</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Methods --}}
        <div class="col-lg-4">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Payment Methods</h6>
                    @forelse($paymentMethods as $method)
                        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div>
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $method->display_name }}</div>
                                <div style="font-size:0.75rem;color:var(--hb-gray-600);">Expires {{ $method->exp_month }}/{{ $method->exp_year }} @if($method->is_default) &middot; Default @endif</div>
                            </div>
                            <form method="POST" action="{{ route('agency.billing.payment-methods.destroy', $method) }}" onsubmit="return confirm('Remove this payment method?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">No payment methods on file yet. A card is added automatically the first time you complete checkout.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Available Plans --}}
    <div class="mb-4">
        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Available Plans</h6>
        <div class="row g-3">
            @foreach($plans as $plan)
                <div class="col-md-3 col-6">
                    <div class="card h-100" style="border-radius:1rem;border:{{ $currentPlan?->id === $plan->id ? '2px solid var(--hb-emerald-700)' : 'none' }};box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-3 text-center">
                            <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $plan->name }}</div>
                            <div style="font-size:1.4rem;font-weight:700;color:var(--hb-emerald-700);margin:0.5rem 0;">
                                ${{ number_format($plan->price_monthly, 0) }}<span style="font-size:0.75rem;color:var(--hb-gray-600);">/mo</span>
                            </div>
                            @if($plan->trial_days)
                                <div style="font-size:0.7rem;color:var(--hb-gray-600);margin-bottom:0.5rem;">{{ $plan->trial_days }}-day free trial</div>
                            @endif

                            @if($currentPlan?->id === $plan->id)
                                <span class="hb-badge-verified">Current Plan</span>
                            @elseif($plan->code === 'free')
                                @if($currentPlan && (float) $currentPlan->price_monthly > 0)
                                    <form method="POST" action="{{ route('agency.billing.downgrade-to-free') }}" onsubmit="return confirm('Downgrade to the Free plan?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100" style="border-radius:0.5rem;">Downgrade</button>
                                    </form>
                                @endif
                            @elseif($isLocalPlaceholder || !$subscription)
                                <form method="POST" action="{{ route('agency.billing.checkout') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <input type="hidden" name="billing_cycle" value="monthly">
                                    <button type="submit" class="hb-btn-primary" style="padding:0.4rem 1rem;font-size:0.85rem;">Choose Plan</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('agency.billing.change-plan') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <input type="hidden" name="billing_cycle" value="monthly">
                                    <button type="submit" class="btn btn-outline-primary btn-sm w-100" style="border-radius:0.5rem;">
                                        {{ $plan->sort_order > $currentPlan->sort_order ? 'Upgrade' : 'Downgrade' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-3" style="max-width:320px;">
            <form method="POST" action="{{ route('agency.billing.checkout') }}" class="d-flex gap-2">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plans->firstWhere('code', '!=', 'free')?->id }}">
                <input type="hidden" name="billing_cycle" value="monthly">
                <input type="text" name="coupon_code" class="hb-form-control" placeholder="Coupon code" style="font-size:0.85rem;">
                <button type="submit" class="btn btn-outline-secondary btn-sm">Apply</button>
            </form>
        </div>
    </div>

    {{-- Payment / Billing History --}}
    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            <div class="p-4 pb-0"><h6 class="fw-bold" style="color:var(--hb-gray-900);">Billing History</h6></div>
            @if($invoices->isEmpty())
                <div class="text-center py-5"><p style="color:var(--hb-gray-600);margin:0;">No invoices yet.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3">Invoice</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                @php ['bg' => $bg, 'text' => $text] = $invoice->status->badgeColor(); @endphp
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3">{{ $invoice->invoice_number }}</td>
                                    <td class="px-4 py-3">{{ $invoice->invoice_type->label() }}</td>
                                    <td class="px-4 py-3">${{ number_format((float) $invoice->total_amount, 2) }}</td>
                                    <td class="px-4 py-3"><span style="font-size:0.75rem;font-weight:600;background:{{ $bg }};color:{{ $text }};padding:0.2rem 0.6rem;border-radius:999px;">{{ $invoice->status->label() }}</span></td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $invoice->created_at->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('agency.billing.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">View</a>
                                        @if($invoice->pdf_path)
                                            <a href="{{ route('agency.billing.invoices.download', $invoice) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;"><i class="bi bi-download"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $invoices->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Payment History — individual payment attempts, distinct from
         Billing History above (invoices). A retried invoice shows
         every attempt (failed and succeeded) here. --}}
    <div class="card mt-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            <div class="p-4 pb-0"><h6 class="fw-bold" style="color:var(--hb-gray-900);">Payment History</h6></div>
            @if($payments->isEmpty())
                <div class="text-center py-5"><p style="color:var(--hb-gray-600);margin:0;">No payment attempts yet.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Invoice</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Method</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $payment->created_at->format('M d, Y') }}</td>
                                    <td class="px-4 py-3">{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">${{ number_format((float) $payment->amount, 2) }}</td>
                                    <td class="px-4 py-3">{{ ucfirst($payment->method) }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $paymentBadges = ['succeeded' => ['var(--hb-emerald-100)', 'var(--hb-emerald-700)'], 'failed' => ['#FEF2F2', '#991B1B'], 'refunded' => ['#EEF2FF', '#3730A3'], 'pending' => ['#FFFBEB', '#92400E']];
                                            [$pBg, $pText] = $paymentBadges[$payment->status->value] ?? ['#F3F4F6', '#374151'];
                                        @endphp
                                        <span style="font-size:0.75rem;font-weight:600;background:{{ $pBg }};color:{{ $pText }};padding:0.2rem 0.6rem;border-radius:999px;">{{ $payment->status->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3" style="color:#991B1B;font-size:0.8rem;">{{ $payment->failure_reason }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>
</x-agency-layout>
