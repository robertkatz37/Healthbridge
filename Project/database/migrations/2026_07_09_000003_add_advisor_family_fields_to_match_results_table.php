<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_results', function (Blueprint $table) {
            // Distinct from is_advisor_approved (already existed, Phase 2):
            // this is the FAMILY's own "Add to Shortlist" action, not the
            // advisor's professional curation — kept as two separate
            // columns so a family's personal shortlisting and an
            // advisor's approval are independently visible and don't
            // overwrite one another.
            $table->boolean('is_family_shortlisted')->default(false)->after('is_advisor_approved');
            // Lets an advisor suppress a computed match from the family's
            // view (e.g. an agency they know from experience isn't a good
            // fit despite scoring well) WITHOUT altering the underlying
            // compatibility_score/score_breakdown — "Override
            // Recommendations manually (without changing algorithm
            // results)" from the Advisor Experience requirement.
            $table->boolean('is_hidden_by_advisor')->default(false)->after('is_family_shortlisted');
            $table->text('advisor_override_note')->nullable()->after('is_hidden_by_advisor');
        });
    }

    public function down(): void
    {
        Schema::table('match_results', function (Blueprint $table) {
            $table->dropColumn(['is_family_shortlisted', 'is_hidden_by_advisor', 'advisor_override_note']);
        });
    }
};
