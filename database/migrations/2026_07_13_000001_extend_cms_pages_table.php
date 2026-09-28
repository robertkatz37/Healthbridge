<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropIndex('cms_pages_status_index');
            $table->dropColumn('status');
        });

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('body');
            $table->string('page_type', 30)->default('custom')->after('slug');
            $table->string('template', 50)->default('default')->after('page_type');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->timestamp('scheduled_at')->nullable()->after('published_at');
            $table->index('status');
            $table->index('page_type');
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['page_type']);
            $table->dropColumn(['status', 'page_type', 'template', 'published_at', 'scheduled_at']);
        });

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->enum('status', ['draft', 'published'])->default('draft')->after('body');
            $table->index('status');
        });
    }
};
