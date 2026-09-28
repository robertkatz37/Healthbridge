<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every column here backs an explicit Phase 12 matching factor
        // that had no existing data model — see DATABASE_DECISIONS.md for
        // the full reasoning. Simple booleans/strings directly on
        // `agencies`, consistent with how comparable single-fact
        // attributes (is_featured, is_veteran on care_seekers) were added
        // in earlier phases, rather than a more flexible but harder-to-
        // query key/value attributes table.
        Schema::table('agencies', function (Blueprint $table) {
            $table->json('languages')->nullable()->after('description')
                ->comment('Languages supported by agency staff, for the Languages Spoken matching factor');
            $table->json('accepted_insurance_providers')->nullable()->after('languages');
            $table->boolean('accepts_medicaid')->default(false)->after('accepted_insurance_providers');
            $table->boolean('accepts_medicare')->default(false)->after('accepts_medicaid');
            $table->boolean('is_pet_friendly')->default(false)->after('accepts_medicare');
            $table->boolean('is_wheelchair_accessible')->default(false)->after('is_pet_friendly');
            $table->boolean('is_veteran_friendly')->default(false)->after('is_wheelchair_accessible');
            $table->string('religious_affiliation')->nullable()->after('is_veteran_friendly');
            $table->string('gender_served', 10)->default('any')->after('religious_affiliation')
                ->comment('any, male, or female — most agencies serve any');
            $table->boolean('has_availability')->default(true)->after('gender_served')
                ->comment('Agency-toggleable flag for the Availability matching factor — no capacity/vacancy tracking system exists yet, this is intentionally simple');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'languages', 'accepted_insurance_providers', 'accepts_medicaid', 'accepts_medicare',
                'is_pet_friendly', 'is_wheelchair_accessible', 'is_veteran_friendly',
                'religious_affiliation', 'gender_served', 'has_availability',
            ]);
        });
    }
};
