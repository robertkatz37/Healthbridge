<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_seekers', function (Blueprint $table) {
            // Activities of Daily Living the care seeker needs help with —
            // stored as a JSON array of AdlType enum values rather than
            // separate boolean columns, since the set is naturally a
            // multi-select and keeping it as one column avoids a 7-column
            // sprawl for what is conceptually a single "ADL needs" fact.
            $table->json('adl_needs')->nullable()->after('behavioral_notes');
            $table->string('insurance_provider')->nullable()->after('is_veteran');
            $table->boolean('has_ltc_insurance')->default(false)->after('insurance_provider');
            $table->string('move_in_timeline')->nullable()->after('has_ltc_insurance');
            $table->string('photo_path')->nullable()->after('move_in_timeline');
        });
    }

    public function down(): void
    {
        Schema::table('care_seekers', function (Blueprint $table) {
            $table->dropColumn(['adl_needs', 'insurance_provider', 'has_ltc_insurance', 'move_in_timeline', 'photo_path']);
        });
    }
};
