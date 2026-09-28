<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Consolidates what the SRS feature list separately calls "Care Types" and
        // "Agency Categories" into one admin-editable lookup table (DATABASE_DECISIONS.md
        // §2). The fixed CareType PHP enum (used on care_seekers.care_type_needed) maps
        // conceptually 1:1 to this table's `code` column but stays code-level since a
        // care seeker's need is a closed set, while agency classification may grow.
        Schema::create('agency_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('e.g. assisted_living, memory_care');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_categories');
    }
};
