<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            // 'advisor' or 'agency' — who wrote it. Kept as an explicit
            // column rather than inferred from author_id's role, since a
            // user's role can change over time but a note's authorship
            // context at the time of writing should not.
            $table->string('author_type', 20);
            // Controls whether an advisor-authored note is shown to the
            // agency ("View Advisor Notes (only shared notes)"). Always
            // implicitly true for the agency's own notes.
            $table->boolean('visible_to_agency')->default(false);
            // Controls whether ANY note (advisor- or agency-authored) is
            // shown to the family — families never see agency notes
            // directly unless an advisor explicitly relays them this way,
            // consistent with "Families should never contact agencies
            // directly."
            $table->boolean('visible_to_family')->default(false);
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_notes');
    }
};
