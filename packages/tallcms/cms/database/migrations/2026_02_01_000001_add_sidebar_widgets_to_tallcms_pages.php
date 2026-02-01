<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tallcms_pages', function (Blueprint $table) {
            $table->json('sidebar_widgets')->nullable()->after('template');
        });
    }

    public function down(): void
    {
        Schema::table('tallcms_pages', function (Blueprint $table) {
            $table->dropColumn('sidebar_widgets');
        });
    }
};
