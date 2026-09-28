<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisor_notes', function (Blueprint $table) {
            // Nullable — existing family-level notes (pre-Phase-11) remain
            // valid; new notes are typically tied to a specific lead.
            $table->foreignId('lead_id')->nullable()->after('family_id')->constrained()->cascadeOnDelete();
            // Distinguishes "Lead Notes" (false — the advisor's own working
            // log: calls, emails, meetings) from "Internal Comments" (true
            // — staff-only remarks, e.g. an advisor_manager's note on a
            // team member's handling of a case). Deliberately a separate
            // boolean rather than folding into note_type's existing enum
            // (call/email/sms/meeting/general): note_type describes the
            // communication channel, is_internal describes visibility —
            // two orthogonal concerns, not more values of the same one.
            // Also avoids an ALTER of the existing native enum column,
            // which isn't portably available without doctrine/dbal.
            $table->boolean('is_internal')->default(false)->after('note_type');
        });
    }

    public function down(): void
    {
        Schema::table('advisor_notes', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropColumn(['lead_id', 'is_internal']);
        });
    }
};
