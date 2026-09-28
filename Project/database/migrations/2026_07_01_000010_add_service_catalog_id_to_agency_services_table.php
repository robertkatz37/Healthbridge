<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Runs after services_catalog (000005) so the FK constraint is valid.
        Schema::table('agency_services', function (Blueprint $table) {
            $table->foreignId('service_catalog_id')->nullable()->after('agency_id')
                ->constrained('services_catalog')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agency_services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_catalog_id');
        });
    }
};
