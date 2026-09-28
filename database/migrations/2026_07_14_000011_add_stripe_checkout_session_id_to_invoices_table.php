<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stripe's own documentation is explicit that a webhook event can
     * be delivered more than once (network retries on their end, or a
     * slow response from this app causing Stripe to retry before the
     * first attempt's 200 is received) — checkout.session.completed
     * had no protection against this at all. A redelivered event would
     * silently create a second invoice and a second real charge
     * record for the same purchase. This column, checked before
     * creating an invoice from ANY checkout session (subscription or
     * one-time), is the idempotency key: a unique DB constraint is a
     * stronger guarantee than an application-level "does this exist"
     * check, since it holds even under concurrent webhook delivery.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id')->nullable()->unique()->after('stripe_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('stripe_checkout_session_id');
        });
    }
};
