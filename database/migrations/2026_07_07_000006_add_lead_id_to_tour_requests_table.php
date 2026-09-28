<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Finally builds out the UI/controllers for tour_requests (schema
        // existed since Phase 2, deferred at Phase 9) — tied to the
        // advisor's Lead pipeline via this new nullable FK.
        Schema::table('tour_requests', function (Blueprint $table) {
            $table->foreignId('lead_id')->nullable()->after('care_seeker_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tour_requests', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropColumn('lead_id');
        });
    }
};
