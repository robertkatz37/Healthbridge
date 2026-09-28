<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * All invoices across every agency — "Featured Listing Purchases" is
 * deliberately a filter on this same page (invoice_type=featured_listing)
 * rather than a separate page, since it's the exact same Invoice model
 * and table; a parallel page would just be this one with a permanent
 * WHERE clause baked in.
 */
class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('billing.manage_all') || $request->user()->can('invoices.view'), 403);

        $query = Invoice::with(['agency', 'payments'])->latest();

        if ($request->filled('type')) {
            $query->where('invoice_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('agency')) {
            $query->whereHas('agency', fn ($q) => $q->where('name', 'like', '%' . $request->agency . '%'));
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('admin.billing.invoices.index', compact('invoices'));
    }

    public function show(Request $request, Invoice $invoice): View
    {
        abort_unless($request->user()->can('billing.manage_all') || $request->user()->can('invoices.view'), 403);

        $invoice->load(['items', 'payments.refunds', 'agency']);

        return view('admin.billing.invoices.show', compact('invoice'));
    }
}
