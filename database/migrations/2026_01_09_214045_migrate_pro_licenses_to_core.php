<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrates existing Pro licenses from tallcms_pro_licenses to the new
     * core tallcms_plugin_licenses table.
     */
    public function up(): void
    {
        // Guard: Only migrate if old table exists
        if (! Schema::hasTable('tallcms_pro_licenses')) {
            return;
        }

        // Guard: Only migrate if new table exists
        if (! Schema::hasTable('tallcms_plugin_licenses')) {
            return;
        }

        // Guard: Prevent duplicates - check if already migrated
        $exists = DB::table('tallcms_plugin_licenses')
            ->where('plugin_slug', 'tallcms/pro')
            ->exists();

        if ($exists) {
            return;
        }

        // Select most recent row (handles multiple re-activations)
        // Use id as tiebreaker in case updated_at is null
        $proLicense = DB::table('tallcms_pro_licenses')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (! $proLicense) {
            return; // No license to migrate
        }

        // Migrate to core table
        DB::table('tallcms_plugin_licenses')->insert([
            'plugin_slug' => 'tallcms/pro',
            'license_key' => $proLicense->license_key,
            'license_source' => 'anystack', // Explicit, never null
            'status' => $proLicense->status ?? 'active',
            'domain' => $proLicense->domain,
            'activated_at' => $proLicense->activated_at,
            'expires_at' => $proLicense->expires_at,
            'last_validated_at' => $proLicense->last_validated_at,
            'metadata' => $proLicense->validation_response ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * Note: This only removes the migrated record, not the original.
     * The original Pro license table remains untouched.
     */
    public function down(): void
    {
        DB::table('tallcms_plugin_licenses')
            ->where('plugin_slug', 'tallcms/pro')
            ->delete();
    }
};
