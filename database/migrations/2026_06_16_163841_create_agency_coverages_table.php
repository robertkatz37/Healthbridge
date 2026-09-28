<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NOTE (Phase 2 fix): original migration created table "agency_coverage"
        // (singular) which mismatched the AgencyCoverage model's implicit
        // "agency_coverages" table name. Corrected here — see DATABASE_DECISIONS.md.
        Schema::create('agency_coverages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('city');
            $table->string('state');
            $table->string('zip_code')->nullable();
            $table->unsignedInteger('radius_miles')->nullable();
            $table->timestamps();

            $table->index(['city', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_coverages');
    }
};
