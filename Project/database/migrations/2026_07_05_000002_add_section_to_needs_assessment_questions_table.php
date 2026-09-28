<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('needs_assessment_questions', function (Blueprint $table) {
            // Groups questions into the preference categories from SRS/Phase 9
            // (care_type, budget, location, medical, mobility, memory, adls,
            // behavioral, languages, insurance, timeline). Multiple sections
            // are combined into one wizard UI step by
            // NeedsAssessmentService::STEP_SECTIONS — section is a data/
            // scoring grouping, not a 1:1 wizard-step boundary; see
            // DATABASE_DECISIONS.md.
            $table->string('section')->nullable()->after('code');
            $table->unsignedSmallInteger('section_order')->default(0)->after('section');

            $table->index('section');
        });
    }

    public function down(): void
    {
        Schema::table('needs_assessment_questions', function (Blueprint $table) {
            $table->dropColumn(['section', 'section_order']);
        });
    }
};
