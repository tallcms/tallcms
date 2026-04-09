<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tallcms_menus', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('tallcms_menus'))->pluck('name');

            if ($indexes->contains('tallcms_menus_location_unique')) {
                $table->dropUnique('tallcms_menus_location_unique');
            }

            if (! $indexes->contains('tallcms_menus_site_location_unique')) {
                $table->unique(['site_id', 'location'], 'tallcms_menus_site_location_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tallcms_menus', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('tallcms_menus'))->pluck('name');

            if ($indexes->contains('tallcms_menus_site_location_unique')) {
                $table->dropUnique('tallcms_menus_site_location_unique');
            }

            if (! $indexes->contains('tallcms_menus_location_unique')) {
                $table->unique('location', 'tallcms_menus_location_unique');
            }
        });
    }
};
