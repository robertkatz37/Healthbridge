<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('code');
        });

        // Hyphenated form of the existing code (e.g. "home_care" ->
        // "home-care") for clean public URLs — the code column stays
        // the internal identifier used elsewhere (permissions, seeders).
        foreach (\App\Models\AgencyCategory::whereNull('slug')->get() as $category) {
            $category->update(['slug' => str_replace('_', '-', $category->code)]);
        }
    }

    public function down(): void
    {
        Schema::table('agency_categories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
