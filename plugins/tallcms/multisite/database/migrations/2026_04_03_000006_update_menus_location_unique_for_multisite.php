<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tallcms_menus', function (Blueprint $table) {
            // Drop the single-column unique on location — with multisite,
            // the same location (e.g. "header") can exist on different sites.
            $table->dropUnique('tallcms_menus_location_unique');

            // Replace with a composite unique: one location per site.
            $table->unique(['site_id', 'location'], 'tallcms_menus_site_location_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tallcms_menus', function (Blueprint $table) {
            $table->dropUnique('tallcms_menus_site_location_unique');
            $table->unique('location', 'tallcms_menus_location_unique');
        });
    }
};
