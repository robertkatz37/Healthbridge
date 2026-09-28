<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table normalizing plan limits (max_locations, max_leads_per_month,
        // allows_featured_listing, etc.) instead of fixed columns on `plans`, so new
        // gateable features can be added without altering the plans table schema.
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_feature_id')->constrained()->cascadeOnDelete();
            $table->string('value')->comment('e.g. "4", "true", "unlimited"');
            $table->timestamps();

            $table->unique(['plan_id', 'subscription_feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
