<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('award_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['agency_id', 'award_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('awards');
    }
};
