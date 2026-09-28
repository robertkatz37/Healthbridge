<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A Lead is the Advisor's own CRM case for a Family — distinct
        // from a Referral (Phase 14), which represents a specific
        // Family-to-Agency introduction. One Lead can eventually lead to
        // multiple Referrals once an advisor shortlists several agencies
        // for the family; that relationship will be established when
        // Phase 14 builds the Referral workflow, not here.
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('care_seeker_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('advisors')->nullOnDelete();
            $table->string('status', 30)->default('new');
            $table->string('source', 30)->default('manual');
            // Snapshot of the territory matched at assignment time, for
            // reporting/debugging routing decisions — not a live lookup.
            $table->string('territory_city')->nullable();
            $table->string('territory_state')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('closed_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['advisor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
