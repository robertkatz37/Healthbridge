<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('needs_assessments', function (Blueprint $table) {
            // Explicitly tracks when matching was last RUN, independent of
            // whether any individual match_results row actually changed.
            // Needed because Eloquent's dirty-checking skips the UPDATE
            // (and therefore skips bumping updated_at) when a regenerated
            // score is byte-identical to what's already stored — which is
            // the common case when nothing score-relevant changed. Without
            // this, AgencyRecommendationService::isStale() comparing
            // against match_results.updated_at would never resolve:
            // every request would see the same "stale" comparison and
            // re-trigger regeneration forever. Caught via smoke testing
            // before shipping.
            $table->timestamp('last_matched_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('needs_assessments', function (Blueprint $table) {
            $table->dropColumn('last_matched_at');
        });
    }
};
