<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_replies', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('body');
        });

        Schema::create('review_reply_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_reply_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_reply_revisions');
        Schema::table('review_replies', function (Blueprint $table) {
            $table->dropColumn('edited_at');
        });
    }
};
