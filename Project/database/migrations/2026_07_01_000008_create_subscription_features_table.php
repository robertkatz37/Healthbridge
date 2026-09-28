<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_features', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('e.g. max_locations, max_leads_per_month, featured_listing');
            $table->string('name');
            $table->enum('value_type', ['boolean', 'integer', 'unlimited'])->default('integer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_features');
    }
};
