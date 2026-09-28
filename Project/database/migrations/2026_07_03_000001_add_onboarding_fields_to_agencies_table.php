<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            // Drives the 5-step onboarding wizard's resumable progress —
            // see AgencyOnboardingService. 1-5 while in progress, null once
            // submitted for review (onboarding_completed_at is set instead).
            $table->unsignedTinyInteger('onboarding_step')->default(1)->after('status');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['onboarding_step', 'onboarding_completed_at']);
        });
    }
};
