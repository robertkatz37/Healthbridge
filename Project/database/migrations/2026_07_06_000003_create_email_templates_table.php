<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Seeded with one row per system email event this phase wires up
        // (the 5 Agency moderation notifications from Phase 8). Editable
        // by Super Admin via the admin panel; EmailTemplateService renders
        // {{variable}} placeholders. Templates are NOT freely creatable by
        // admins — the `key` values are fixed to actual code call sites,
        // consistent with how a real "system email templates" screen
        // works (you customize existing system emails, you don't invent
        // arbitrary new ones with no code behind them).
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('subject');
            $table->longText('body');
            $table->text('description')->nullable()->comment('Explains available {{variable}} placeholders to the admin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
