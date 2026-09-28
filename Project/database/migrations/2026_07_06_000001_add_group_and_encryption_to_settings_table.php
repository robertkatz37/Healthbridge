<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Groups settings into the tabs shown in the admin Settings UI
            // (general, mail, branding, system) — the same generic
            // key/value table from Phase 2 is reused rather than one table
            // per settings category, since that's exactly the "extensible
            // architecture" Phase 10 asks for: a new settings group needs
            // no migration, just new seeded rows.
            $table->string('group')->default('general')->after('key');
            // Sensitive values (SMTP password, future API keys) are
            // encrypted at rest via Laravel's Crypt facade — see
            // Setting model's accessor/mutator.
            $table->boolean('is_encrypted')->default(false)->after('type');

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['group', 'is_encrypted']);
        });
    }
};
