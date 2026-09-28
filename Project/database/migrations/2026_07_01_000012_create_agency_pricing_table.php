<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('room_type')->comment('e.g. studio, shared, one_bedroom');
            $table->string('care_level')->nullable();
            $table->decimal('monthly_price', 10, 2);
            $table->timestamps();

            $table->index(['agency_id', 'room_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_pricing');
    }
};
