<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('user_id');
            $table->string('phone')->nullable()->after('license_number');
            $table->text('bio')->nullable()->after('phone');
            $table->json('languages')->nullable()->after('bio');
            $table->json('specialties')->nullable()->after('languages');
            $table->string('certifications')->nullable()->after('specialties');
            // Structured as [{day: 'monday', start: '09:00', end: '17:00', enabled: true}, ...]
            $table->json('working_hours')->nullable()->after('certifications');
            $table->boolean('is_available')->default(true)->after('working_hours');
            $table->text('email_signature')->nullable()->after('is_available');
        });
    }

    public function down(): void
    {
        Schema::table('advisors', function (Blueprint $table) {
            $table->dropColumn([
                'photo_path', 'phone', 'bio', 'languages', 'specialties',
                'certifications', 'working_hours', 'is_available', 'email_signature',
            ]);
        });
    }
};
