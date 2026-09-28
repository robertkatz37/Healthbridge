<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('externally-safe identifier for API/links');
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('care_seeker_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('pending')
                ->comment('App\\Enums\\ReferralStatus backed enum: pending, accepted, tour_scheduled, visited, converted, cancelled, rejected');
            $table->enum('source', ['matching_engine', 'advisor_manual', 'affiliate'])->default('matching_engine');
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['family_id', 'status']);
            $table->index(['advisor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
