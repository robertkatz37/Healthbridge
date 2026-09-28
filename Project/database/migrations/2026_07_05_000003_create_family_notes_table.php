<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Private notes a family writes for themselves — reminders,
        // observations, questions to ask an agency, etc. Distinct from
        // agency_admin_notes (Phase 8), which is staff-authored and
        // internal-only; these are family-authored and family-only,
        // consistent with this module's consumer-facing scope.
        Schema::create('family_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('care_seeker_id')->nullable()->constrained()->cascadeOnDelete()
                ->comment('Null = general family note, not tied to one care seeker');
            $table->text('note');
            $table->timestamps();

            $table->index('family_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_notes');
    }
};
