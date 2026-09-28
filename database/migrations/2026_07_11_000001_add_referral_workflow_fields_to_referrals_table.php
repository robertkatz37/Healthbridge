<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            // Links a Referral back to the originating Lead (Phase 11) —
            // the entity distinction established in Phase 11 stands: a
            // Lead is the advisor's whole-search CRM case for a family,
            // a Referral is one specific family-to-agency introduction
            // that Lead can produce. Nullable for schema safety, but
            // every Referral created through this phase's workflow
            // always sets it.
            $table->foreignId('lead_id')->nullable()->after('advisor_id')->constrained()->nullOnDelete();

            $table->string('priority', 10)->default('medium')->after('status');

            // Distinct from created_at (which marks when the advisor
            // shortlisted/drafted the referral) — sent_at marks the
            // actual "Sent to Agency" transition, the point at which an
            // agency first becomes aware of the referral.
            $table->timestamp('sent_at')->nullable()->after('priority');
            $table->timestamp('agency_responded_at')->nullable()->after('sent_at');
            $table->timestamp('closed_at')->nullable()->after('agency_responded_at');
            $table->text('closed_reason')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropColumn(['lead_id', 'priority', 'sent_at', 'agency_responded_at', 'closed_at', 'closed_reason']);
        });
    }
};
