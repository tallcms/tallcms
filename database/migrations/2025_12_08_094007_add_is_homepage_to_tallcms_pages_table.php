<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tallcms_pages', function (Blueprint $table) {
            $table->boolean('is_homepage')->default(false)->after('status');
            $table->index(['is_homepage', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tallcms_pages', function (Blueprint $table) {
            $table->dropIndex(['is_homepage', 'status']);
            $table->dropColumn('is_homepage');
        });
    }
};
