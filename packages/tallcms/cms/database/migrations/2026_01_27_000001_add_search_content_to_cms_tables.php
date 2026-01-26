<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add search_content to pages (longText for LIKE compatibility across all DBs)
        // Using longText instead of JSON because PostgreSQL's jsonb doesn't support
        // LIKE queries without explicit casting. Spatie Translatable stores JSON
        // in text columns anyway, so this works for translations.
        Schema::table('tallcms_pages', function (Blueprint $table) {
            $table->longText('search_content')->nullable()->after('content');
        });

        // Add search_content to posts
        Schema::table('tallcms_posts', function (Blueprint $table) {
            $table->longText('search_content')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('tallcms_pages', function (Blueprint $table) {
            $table->dropColumn('search_content');
        });

        Schema::table('tallcms_posts', function (Blueprint $table) {
            $table->dropColumn('search_content');
        });
    }
};
