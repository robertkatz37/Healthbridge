<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-user opt-in/out of notification channels, keyed by event
            // type e.g. {"lead_assigned": {"mail": true, "database": true}}.
            // A missing key defaults to "on" (see NotificationPreferenceService)
            // so existing users are unaffected until they explicitly change
            // a preference.
            $table->json('notification_preferences')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
