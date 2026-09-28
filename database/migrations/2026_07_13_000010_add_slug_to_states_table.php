<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable + unique from the start rather than nullable then
        // ->change()'d to NOT NULL after backfilling — Schema::change()
        // needs doctrine/dbal, not installed here. Application-level
        // (LocationSeeder / model creation) is responsible for always
        // supplying a slug on new rows; this migration backfills every
        // existing row in the same pass so the column is never actually
        // left null in practice.
        Schema::table('states', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        foreach (\App\Models\State::whereNull('slug')->get() as $state) {
            $state->update(['slug' => Str::slug($state->name)]);
        }
    }

    public function down(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
