<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('needs_assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('stable reference key used in display_condition rules');
            $table->text('question_text');
            $table->enum('input_type', ['single_select', 'multi_select', 'number', 'text', 'scale']);
            $table->json('options')->nullable();
            $table->json('display_condition')->nullable()
                ->comment('e.g. {"question":"mobility","operator":"=","value":"wheelchair"}');
            $table->decimal('weight', 4, 2)->default(1.00);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('needs_assessment_questions');
    }
};
