<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mirrors agency_coverages (Phase 7) exactly — the same
        // "service area" concept applied to advisors instead of agencies,
        // consumed by LeadAssignmentService's territory-based routing.
        Schema::create('advisor_territories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisor_id')->constrained()->cascadeOnDelete();
            $table->string('city');
            $table->string('state');
            $table->unsignedInteger('radius_miles')->nullable();
            $table->timestamps();

            $table->index(['city', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_territories');
    }
};
