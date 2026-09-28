<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_seekers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('gender', 30)->nullable();
            $table->text('medical_conditions')->nullable();
            $table->enum('memory_status', ['none', 'mild', 'moderate', 'severe'])->nullable();
            $table->enum('mobility', ['independent', 'cane_walker', 'wheelchair', 'bedbound'])->nullable();
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->boolean('is_veteran')->default(false);
            $table->string('preferred_city')->nullable();
            $table->string('preferred_state')->nullable();
            $table->json('languages')->nullable();
            $table->string('care_type_needed')->nullable()
                ->comment('CareType enum value; conceptually maps to agency_categories.code');
            $table->text('behavioral_notes')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('internal_notes')->nullable()->comment('advisor-only, never family-visible');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['preferred_state', 'preferred_city']);
            $table->index('care_type_needed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_seekers');
    }
};
