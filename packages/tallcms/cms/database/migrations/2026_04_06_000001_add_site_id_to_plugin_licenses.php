<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tallcms_plugin_licenses', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->after('plugin_slug');

            // Drop old unique on plugin_slug alone
            $table->dropUnique('tallcms_plugin_licenses_plugin_slug_unique');

            // New composite unique: same plugin can be licensed on different sites
            $table->unique(['plugin_slug', 'site_id'], 'plugin_licenses_slug_site_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tallcms_plugin_licenses', function (Blueprint $table) {
            $table->dropUnique('plugin_licenses_slug_site_unique');
            $table->unique('plugin_slug', 'tallcms_plugin_licenses_plugin_slug_unique');
            $table->dropColumn('site_id');
        });
    }
};
