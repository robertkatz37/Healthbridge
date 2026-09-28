<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisor_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('family_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->timestamp('due_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['advisor_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_tasks');
    }
};
