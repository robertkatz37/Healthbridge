<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // invoices.status is already a plain string(20) column (cast to
        // InvoiceStatus only at the Eloquent layer, not a DB-level
        // enum/constraint) — no column drop/recreate needed, unlike
        // Phase 15's cms_pages/blog_posts.status. InvoiceStatus gains
        // Pending/Failed/Refunded cases (see the enum file); the
        // column's existing DB default of 'draft' is left as-is since
        // every invoice going forward is created explicitly through
        // InvoiceService with its own status, not relying on the
        // column default.
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_type', 20)->default('subscription')->after('status'); // subscription | featured_listing | addon | commission
            $table->decimal('subtotal_amount', 10, 2)->default(0)->after('invoice_type');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal_amount');
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('total_amount');
            $table->string('stripe_invoice_id')->nullable()->after('refunded_amount');
            $table->foreignId('coupon_id')->nullable()->after('stripe_invoice_id')->constrained()->nullOnDelete();
            $table->string('pdf_path')->nullable()->after('coupon_id');
            $table->timestamp('paid_at')->nullable()->after('pdf_path');
            $table->index('invoice_type');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_type']);
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn([
                'invoice_type', 'subtotal_amount', 'tax_amount', 'refunded_amount',
                'stripe_invoice_id', 'pdf_path', 'paid_at',
            ]);
        });
    }
};
