<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tallcms_menus', 'site_id')) {
            return;
        }

        Schema::table('tallcms_menus', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('id')
                ->constrained('tallcms_sites')->nullOnDelete();
            $table->index('site_id');
        });
    }

    public function down(): void
    {
        Schema::table('tallcms_menus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
        });
    }
};
