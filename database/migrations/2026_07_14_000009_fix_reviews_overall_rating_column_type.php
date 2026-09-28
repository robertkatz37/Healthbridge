<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * overall_rating was created as unsignedTinyInteger, but it's always
     * been computed as an average across category ratings (see
     * ReviewSubmissionService — round(sum/count, 2)), which is routinely
     * fractional (e.g. 4.67). SQLite's loose column type affinity let a
     * fractional value slide into an "integer" column without complaint
     * in every test run; MySQL enforces the declared type strictly and
     * silently rounds 4.67 to the nearest whole number (5) on insert —
     * discovered by running the suite against a real MySQL database,
     * not SQLite. Two other call sites (AgencyController,
     * AgencyDashboardController) already round(overall_rating) for
     * star-bucket breakdowns specifically because they anticipated this
     * value being fractional — confirming decimal was always the
     * intended type, not an integer.
     *
     * Data-preserving rather than drop+recreate (unlike Phase 15's
     * status-column widenings, which never held meaningful data in the
     * dropped column) — any already-migrated database has real review
     * ratings in this column that a straight drop would destroy. A
     * temporary column holds a numeric copy, then the original is
     * dropped and the temporary one renamed into its place; whole
     * numbers 1-5 cast cleanly to decimal(3,2) with no precision loss.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->decimal('overall_rating_new', 3, 2)->nullable()->after('overall_rating');
        });

        DB::statement('UPDATE reviews SET overall_rating_new = overall_rating');

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('overall_rating');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->renameColumn('overall_rating_new', 'overall_rating');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('overall_rating_old')->nullable()->after('overall_rating');
        });

        DB::statement('UPDATE reviews SET overall_rating_old = ROUND(overall_rating)');

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('overall_rating');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->renameColumn('overall_rating_old', 'overall_rating');
        });
    }
};
