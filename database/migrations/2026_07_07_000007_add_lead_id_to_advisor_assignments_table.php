<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Repurposes advisor_assignments (Phase 2, previously unused by
        // any controller) as the Lead assignment/reassignment audit
        // trail — its existing assigned_at/unassigned_at columns already
        // model exactly this. Every assignment or reassignment creates a
        // new row (closing out the prior row's unassigned_at, if any) via
        // LeadAssignmentService, rather than introducing a parallel table
        // with the same shape.
        Schema::table('advisor_assignments', function (Blueprint $table) {
            $table->foreignId('lead_id')->nullable()->after('family_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('advisor_assignments', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropColumn('lead_id');
        });
    }
};
