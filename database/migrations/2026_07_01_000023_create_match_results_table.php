<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('needs_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->decimal('compatibility_score', 5, 2)->comment('0-100');
            $table->json('score_breakdown')->comment('per-factor sub-scores for transparency, see SRS §18');
            $table->boolean('is_advisor_approved')->default(false);
            $table->unsignedSmallInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['needs_assessment_id', 'agency_id']);
            $table->index('compatibility_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_results');
    }
};
