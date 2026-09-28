<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('family_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reviewer_name');
            $table->enum('reviewer_relationship', ['resident', 'family_member', 'former_resident']);
            $table->string('title')->nullable();
            $table->text('body');
            $table->unsignedTinyInteger('overall_rating')->comment('1-5');
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending_moderation')
                ->comment('App\\Enums\\ReviewStatus: pending_moderation, published, rejected');
            $table->timestamp('published_at')->nullable()->comment('drives recency-weighted scoring, SRS §22');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
