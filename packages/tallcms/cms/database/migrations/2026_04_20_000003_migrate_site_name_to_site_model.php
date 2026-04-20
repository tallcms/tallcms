<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migrate site_name setting values into Site.name model field.
 *
 * After this migration:
 * - Site.name IS the public brand name
 * - site_name setting key is retired
 * - SiteSetting::get('site_name') resolves via alias to Site.name
 *
 * Must run AFTER the core tallcms_sites table exists and default site is guaranteed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tallcms_sites')) {
            return;
        }

        // Step 1: Migrate per-site overrides to Site.name
        if (Schema::hasTable('tallcms_site_setting_overrides')) {
            $overrides = DB::table('tallcms_site_setting_overrides')
                ->where('key', 'site_name')
                ->get();

            foreach ($overrides as $override) {
                DB::table('tallcms_sites')
                    ->where('id', $override->site_id)
                    ->update(['name' => $override->value]);
            }
        }

        // Step 2: Migrate global site_name to default site
        if (Schema::hasTable('tallcms_site_settings')) {
            $global = DB::table('tallcms_site_settings')
                ->where('key', 'site_name')
                ->value('value');

            if ($global) {
                DB::table('tallcms_sites')
                    ->where('is_default', true)
                    ->where(function ($q) {
                        $q->whereNull('name')
                            ->orWhere('name', 'Default Site')
                            ->orWhere('name', 'My Site');
                    })
                    ->update(['name' => $global]);
            }
        }

        // Step 3: Delete legacy site_name rows
        if (Schema::hasTable('tallcms_site_setting_overrides')) {
            DB::table('tallcms_site_setting_overrides')
                ->where('key', 'site_name')
                ->delete();
        }

        if (Schema::hasTable('tallcms_site_settings')) {
            DB::table('tallcms_site_settings')
                ->where('key', 'site_name')
                ->delete();
        }
    }

    public function down(): void
    {
        // No rollback — site_name is now part of Site.name
    }
};
