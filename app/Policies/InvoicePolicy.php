<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('billing.manage_all') || $actor->can('billing.manage_own') || $actor->can('invoices.view');
    }

    public function view(User $actor, Invoice $invoice): bool
    {
        return $actor->can('billing.manage_all') || $actor->can('invoices.view')
            || ($actor->can('billing.manage_own') && $actor->currentAgency()?->id === $invoice->agency_id);
    }

    public function manage(User $actor): bool
    {
        return $actor->can('billing.manage_all') || $actor->can('invoices.manage');
    }
}
