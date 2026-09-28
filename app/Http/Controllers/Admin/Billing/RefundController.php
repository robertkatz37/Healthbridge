<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Services\Billing\RefundService;
use App\Services\Billing\StripeGatewayException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function __construct(
        private readonly RefundService $refunds,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('approve', Refund::class);

        $query = Refund::with(['payment.invoice.agency', 'requester', 'approver'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $refundRequests = $query->paginate(20)->withQueryString();

        return view('admin.billing.refunds.index', compact('refundRequests'));
    }

    public function approve(Request $request, Refund $refund): RedirectResponse
    {
        $this->authorize('approve', Refund::class);

        $request->validate(['admin_notes' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->refunds->approve($refund, $request->user(), $request->admin_notes);
        } catch (StripeGatewayException $e) {
            Log::error('Stripe error while approving refund #' . $refund->id . ': ' . $e->getMessage(), [
                'stripe_error_type' => $e->stripeErrorType, 'http_status' => $e->httpStatus,
            ]);
            $message = $e->httpStatus === 401
                ? 'Billing is not yet fully configured on this site (missing Stripe API key) — see Admin > Stripe Settings.'
                : 'Stripe declined the refund request. Please try again shortly.';

            return back()->withErrors(['stripe' => $message]);
        }

        return back()->with('status', 'refund-approved');
    }

    public function reject(Request $request, Refund $refund): RedirectResponse
    {
        $this->authorize('approve', Refund::class);

        $request->validate(['admin_notes' => ['required', 'string', 'max:1000']]);

        $this->refunds->reject($refund, $request->user(), $request->admin_notes);

        return back()->with('status', 'refund-rejected');
    }
}
