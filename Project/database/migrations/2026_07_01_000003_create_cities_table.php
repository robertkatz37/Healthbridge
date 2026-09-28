<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Structure-only seed at Phase 2 (per PROJECT_ROADMAP.md); full city data
        // import is a Phase 14 (CMS/SEO) content task, not a schema task.
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('population')->nullable();
            $table->boolean('is_metro_hub')->default(false)
                ->comment('Flags the ~60 largest metro-area cities used for homepage/SEO linking');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['state_id', 'name']);
            $table->index('is_metro_hub');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
