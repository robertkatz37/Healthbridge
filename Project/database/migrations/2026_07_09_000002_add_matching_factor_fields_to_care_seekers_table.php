<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_seekers', function (Blueprint $table) {
            $table->string('religious_preference')->nullable()->after('is_veteran');
            $table->boolean('wants_pet_friendly')->default(false)->after('religious_preference');
            $table->boolean('uses_medicaid')->default(false)->after('wants_pet_friendly');
            $table->boolean('uses_medicare')->default(false)->after('uses_medicaid');
            // Nullable — true distance calculation only where set. No
            // geocoding provider is integrated in this phase (that's
            // Phase 20 / Search's Google Places scope, not Matching); see
            // DistanceCalculatorService for the documented tiered
            // city/state fallback used when coordinates are absent.
            $table->decimal('lat', 10, 7)->nullable()->after('preferred_state');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('care_seekers', function (Blueprint $table) {
            $table->dropColumn(['religious_preference', 'wants_pet_friendly', 'uses_medicaid', 'uses_medicare', 'lat', 'lng']);
        });
    }
};
