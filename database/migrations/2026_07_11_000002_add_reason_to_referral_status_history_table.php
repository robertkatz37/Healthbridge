<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_status_history', function (Blueprint $table) {
            // Mirrors lead_status_history.reason (Phase 11) — required
            // when transitioning to Closed Lost/Cancelled/Agency Declined,
            // so the audit trail records *why*, not just *what changed*.
            $table->text('reason')->nullable()->after('to_status');
        });
    }

    public function down(): void
    {
        Schema::table('referral_status_history', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
