<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Populated automatically for every outgoing email via a listener
        // on Laravel's MessageSent event (successes) and JobFailed event
        // (queued notification failures) — see
        // App\Listeners\LogSentEmail / LogFailedEmailJob. This means every
        // mail the app sends is logged here regardless of which feature
        // triggered it (verification, password reset, agency moderation,
        // future billing), without needing to instrument each call site
        // individually.
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_address');
            $table->string('subject')->nullable();
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->string('notification_class')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('to_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
