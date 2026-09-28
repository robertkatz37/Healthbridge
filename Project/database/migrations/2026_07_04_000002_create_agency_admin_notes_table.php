<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Internal-only notes visible to platform staff during moderation
        // review — never exposed on any owner-facing route or view. This
        // is enforced by simply never joining/rendering this table on the
        // agency-owner side of the app, not by a column-level flag.
        Schema::create('agency_admin_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Staff author of the note');
            $table->text('note');
            $table->timestamps();

            $table->index('agency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_admin_notes');
    }
};
