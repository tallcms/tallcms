<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Core Site table migration.
 *
 * Every TallCMS installation has at least one Site record.
 * If the table already exists (from the multisite plugin), this is a no-op.
 * The multisite plugin adds additional columns (user_id, domain_verified, etc.)
 * via its own migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tallcms_sites')) {
            // Table exists (from multisite plugin or prior install)
            // Ensure a default site exists
            $this->ensureDefaultSite();

            return;
        }

        Schema::create('tallcms_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('theme')->nullable();
            $table->string('locale')->nullable();
            $table->string('uuid')->unique()->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->ensureDefaultSite();
    }

    public function down(): void
    {
        // Don't drop — other migrations may depend on this table
    }

    protected function ensureDefaultSite(): void
    {
        if (DB::table('tallcms_sites')->where('is_default', true)->exists()) {
            return;
        }

        $appUrl = config('app.url', 'http://localhost');
        $domain = strtolower(parse_url($appUrl, PHP_URL_HOST) ?? 'localhost');

        // Check if a site with this domain exists but isn't default
        $existing = DB::table('tallcms_sites')->where('domain', $domain)->first();
        if ($existing) {
            DB::table('tallcms_sites')->where('id', $existing->id)
                ->update(['is_default' => true, 'is_active' => true]);

            return;
        }

        $siteName = 'My Site';
        try {
            $siteName = DB::table('tallcms_site_settings')
                ->where('key', 'site_name')
                ->value('value') ?? config('app.name', 'My Site');
        } catch (\Throwable) {
        }

        DB::table('tallcms_sites')->insert([
            'name' => $siteName,
            'domain' => $domain,
            'uuid' => (string) Str::uuid(),
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
