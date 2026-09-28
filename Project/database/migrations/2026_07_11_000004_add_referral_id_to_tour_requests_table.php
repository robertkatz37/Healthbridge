<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_requests', function (Blueprint $table) {
            // A tour is for one specific agency the family is actually
            // engaging with — belongs to a Referral, not just the
            // broader Lead. lead_id (Phase 11) is kept for the advisor's
            // per-lead tour list; referral_id is the more precise link
            // used by the new Referral timeline/detail pages.
            $table->foreignId('referral_id')->nullable()->after('lead_id')->constrained()->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('confirmed_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tour_requests', function (Blueprint $table) {
            $table->dropForeign(['referral_id']);
            $table->dropColumn(['referral_id', 'confirmed_at', 'completed_at', 'cancelled_at']);
        });
    }
};
