<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('tallcms_menus'))->pluck('name')->all();

        Schema::table('tallcms_menus', function (Blueprint $table) use ($indexes) {
            if (in_array('tallcms_menus_location_unique', $indexes)) {
                $table->dropUnique('tallcms_menus_location_unique');
            }

            if (! in_array('tallcms_menus_site_location_unique', $indexes)) {
                $table->unique(['site_id', 'location'], 'tallcms_menus_site_location_unique');
            }
        });
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('tallcms_menus'))->pluck('name')->all();

        Schema::table('tallcms_menus', function (Blueprint $table) use ($indexes) {
            if (in_array('tallcms_menus_site_location_unique', $indexes)) {
                $table->dropUnique('tallcms_menus_site_location_unique');
            }

            if (! in_array('tallcms_menus_location_unique', $indexes)) {
                $table->unique('location', 'tallcms_menus_location_unique');
            }
        });
    }
};
