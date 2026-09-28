<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mirrors referral_status_history's structure (Phase 2) for
        // consistency — a typed, queryable transition log purpose-built
        // for rendering a status timeline, distinct from the generic
        // activity_log (which also receives an entry for every moderation
        // action per the audit-logging requirement, but isn't optimized
        // for "show me this agency's status history in order").
        Schema::create('agency_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable()->comment('Populated for reject/request-changes/suspend transitions');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('agency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_status_history');
    }
};
