<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('needs_assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('needs_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('needs_assessment_questions')->cascadeOnDelete();
            $table->json('answer_value');
            $table->timestamps();

            $table->unique(['needs_assessment_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('needs_assessment_answers');
    }
};
