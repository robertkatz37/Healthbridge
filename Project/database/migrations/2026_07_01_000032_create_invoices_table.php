<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('status', 20)->default('draft')
                ->comment('App\\Enums\\InvoiceStatus: draft, sent, paid, overdue, void');
            $table->date('due_date');
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
