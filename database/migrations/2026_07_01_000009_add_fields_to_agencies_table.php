<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->foreignId('agency_category_id')->nullable()->after('user_id')
                ->constrained('agency_categories')->nullOnDelete();
            $table->boolean('is_featured')->default(false)->after('status');
            $table->decimal('review_score', 3, 2)->nullable()->after('is_featured')
                ->comment('Denormalized recency-weighted average, recalculated by AgencyScoreRecalculation job');
            $table->decimal('min_monthly_cost', 10, 2)->nullable()->after('review_score');
            $table->decimal('max_monthly_cost', 10, 2)->nullable()->after('min_monthly_cost');
            $table->softDeletes();

            $table->index('is_featured');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_category_id');
            $table->dropColumn(['is_featured', 'review_score', 'min_monthly_cost', 'max_monthly_cost', 'deleted_at']);
        });
    }
};
