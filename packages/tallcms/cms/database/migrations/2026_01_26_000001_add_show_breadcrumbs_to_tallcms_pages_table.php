<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tallcms_pages', function (Blueprint $table) {
            $table->boolean('show_breadcrumbs')->default(true)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('tallcms_pages', function (Blueprint $table) {
            $table->dropColumn('show_breadcrumbs');
        });
    }
};
