<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Commission Foundation (Phase 16) — the existing Commission/
     * CommissionRule pair (built ahead of time, likely Phase 11)
     * already models a platform referral fee (agency_id nullable =
     * platform default, rule_type + amount). This phase extends both
     * with WHO a commission is owed TO (beneficiary_type/id, polymorphic
     * — a User for an advisor commission, null for the existing
     * platform-fee-from-agency case) and WHAT KIND it is
     * (commission_type), so advisor commissions and future affiliate
     * commissions can share the same ledger without a parallel table —
     * "prepare the foundation", not build full payout processing yet.
     */
    public function up(): void
    {
        Schema::table('commission_rules', function (Blueprint $table) {
            $table->string('commission_type', 20)->default('referral_fee')->after('rule_type'); // referral_fee | advisor_commission | affiliate_commission
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->string('commission_type', 20)->default('referral_fee')->after('commission_rule_id');
            $table->nullableMorphs('beneficiary');
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'beneficiary_type', 'beneficiary_id']);
        });
        Schema::table('commission_rules', function (Blueprint $table) {
            $table->dropColumn('commission_type');
        });
    }
};
