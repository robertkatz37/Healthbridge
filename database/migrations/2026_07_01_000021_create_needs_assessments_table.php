<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('needs_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_seeker_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->unsignedSmallInteger('current_step')->default(1);
            $table->json('score_profile')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['care_seeker_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('needs_assessments');
    }
};
