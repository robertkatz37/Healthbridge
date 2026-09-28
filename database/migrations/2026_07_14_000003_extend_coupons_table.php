<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('type', 20)->default('percentage')->after('code'); // percentage | fixed
            $table->decimal('value', 10, 2)->default(0)->after('type');
            $table->string('applies_to', 20)->default('all')->after('value'); // all | subscription | featured_listing
            $table->foreignId('plan_id')->nullable()->after('applies_to')->constrained()->nullOnDelete();
            $table->unsignedInteger('max_uses')->nullable()->after('plan_id');
            $table->unsignedInteger('times_used')->default(0)->after('max_uses');
        });

        // stripe_coupon_id was NOT NULL — a coupon can now be created
        // locally first and synced to Stripe afterward (or never, for
        // an internal-only promo), so it needs to allow null. Changing
        // nullability needs the same drop+recreate treatment as Phase 15's
        // status columns (doctrine/dbal isn't installed for ->change()).
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('stripe_coupon_id');
        });
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('stripe_coupon_id')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['type', 'value', 'applies_to', 'max_uses', 'times_used']);
            $table->dropColumn('stripe_coupon_id');
        });
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('stripe_coupon_id')->after('code');
        });
    }
};
