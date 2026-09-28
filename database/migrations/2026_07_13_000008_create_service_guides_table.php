<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_category_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->longText('intro_content')->nullable();
            $table->json('median_cost_data')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_guides');
    }
};
