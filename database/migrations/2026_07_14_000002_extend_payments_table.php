<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 20)->default('succeeded')->after('method'); // succeeded | failed | refunded | pending
            $table->string('failure_reason')->nullable()->after('status');
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('failure_reason');
            $table->unsignedTinyInteger('retry_count')->default(0)->after('refunded_amount');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'failure_reason', 'refunded_amount', 'retry_count']);
        });
    }
};
