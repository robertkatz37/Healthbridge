<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // The critical eligibility link: "Only verified families who
            // completed a successful Move-In through a Referral may submit
            // reviews." Unique so a single Referral can never produce two
            // reviews — the actual duplicate-prevention mechanism, enforced
            // at the database level, not just in application code.
            $table->foreignId('referral_id')->nullable()->after('family_id')->unique()->constrained()->nullOnDelete();
            $table->boolean('is_anonymous')->default(false)->after('reviewer_relationship');
            $table->boolean('would_recommend')->nullable()->after('overall_rating');
            $table->boolean('is_featured')->default(false)->after('status');
            // Denormalized cache, same pattern as agencies.review_score —
            // avoids a COUNT/SUM query every time votes need to be sorted by.
            $table->unsignedInteger('helpful_count')->default(0)->after('is_featured');
            $table->unsignedInteger('not_helpful_count')->default(0)->after('helpful_count');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['referral_id']);
            $table->dropColumn(['referral_id', 'is_anonymous', 'would_recommend', 'is_featured', 'helpful_count', 'not_helpful_count']);
        });
    }
};
