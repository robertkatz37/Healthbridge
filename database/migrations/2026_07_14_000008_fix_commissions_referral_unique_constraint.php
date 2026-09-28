<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original schema uniquely constrained referral_id alone —
     * correct when a referral only ever produced one commission (the
     * platform fee). Phase 16 adds a second commission_type
     * (advisor_commission) that can exist on the SAME referral
     * alongside the platform fee, so the constraint needs to move to
     * the (referral_id, commission_type) pair: still prevents two
     * referral_fee rows (or two advisor_commission rows) for the same
     * referral, but allows one of each.
     *
     * MySQL refuses to drop a unique index while a foreign key still
     * depends on it ("Cannot drop index ... needed in a foreign key
     * constraint") — SQLite is more permissive and doesn't enforce
     * this, which is why this passed in the SQLite test suite but
     * failed against a real MySQL database. The foreign key has to be
     * dropped first, then the index, then both are recreated — on the
     * new composite unique index, which MySQL is equally happy to
     * back a foreign key with.
     */
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropForeign(['referral_id']);
            $table->dropUnique(['referral_id']);
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->unique(['referral_id', 'commission_type']);
            $table->foreign('referral_id')->references('id')->on('referrals')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropForeign(['referral_id']);
            $table->dropUnique(['referral_id', 'commission_type']);
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->unique('referral_id');
            $table->foreign('referral_id')->references('id')->on('referrals')->cascadeOnDelete();
        });
    }
};
