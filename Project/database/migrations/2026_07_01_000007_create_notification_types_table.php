<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reference/admin-config table: lets Admins see and manage default channel
        // routing per event type (Notification Center settings UI, Phase 16/17).
        // The actual Notification classes remain code-defined; this table does not
        // drive dispatch logic itself, only default channel preferences shown to users.
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('e.g. new_lead, tour_confirmed, invoice_due');
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('default_channels')->nullable()->comment('e.g. ["mail","database"]');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};
