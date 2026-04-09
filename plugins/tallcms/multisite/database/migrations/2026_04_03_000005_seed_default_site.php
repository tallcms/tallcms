<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TallCms\Cms\Models\SiteSetting;

return new class extends Migration
{
    public function up(): void
    {
        // Don't seed if a default site already exists
        if (DB::table('tallcms_sites')->where('is_default', true)->exists()) {
            return;
        }

        // Derive site name from settings or app config
        $siteName = 'Default Site';
        try {
            $siteName = SiteSetting::get('site_name', config('app.name', 'Default Site'));
        } catch (\Throwable) {
            // Settings table may not exist
        }

        // Derive domain from APP_URL, normalize consistently with Site model
        $appUrl = config('app.url', 'http://localhost');
        $domain = $this->normalizeDomain(parse_url($appUrl, PHP_URL_HOST) ?? 'localhost');

        // Assign owner: first super_admin, or first user
        $ownerId = null;
        try {
            $ownerId = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'super_admin')
                ->value('model_has_roles.model_id');
            $ownerId ??= DB::table('users')->orderBy('id')->value('id');
        } catch (\Throwable) {
        }

        // If a site with this domain exists but isn't default, promote it
        $existing = DB::table('tallcms_sites')->where('domain', $domain)->first();
        if ($existing) {
            $updateData = ['is_default' => true, 'is_active' => true, 'updated_at' => now()];
            if ($existing->user_id === null && $ownerId !== null) {
                $updateData['user_id'] = $ownerId;
            }
            DB::table('tallcms_sites')->where('id', $existing->id)->update($updateData);
            $siteId = $existing->id;
        } else {
            $siteId = DB::table('tallcms_sites')->insertGetId([
                'name' => $siteName,
                'domain' => $domain,
                'uuid' => (string) Str::uuid(),
                'user_id' => $ownerId,
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign all existing pages to the default site
        DB::table('tallcms_pages')
            ->whereNull('site_id')
            ->update(['site_id' => $siteId]);

        // Assign all existing menus to the default site
        DB::table('tallcms_menus')
            ->whereNull('site_id')
            ->update(['site_id' => $siteId]);
    }

    public function down(): void
    {
        // Unassign all content from the default site
        $defaultSite = DB::table('tallcms_sites')->where('is_default', true)->first();

        if ($defaultSite) {
            DB::table('tallcms_pages')
                ->where('site_id', $defaultSite->id)
                ->update(['site_id' => null]);

            DB::table('tallcms_menus')
                ->where('site_id', $defaultSite->id)
                ->update(['site_id' => null]);

            DB::table('tallcms_sites')->where('id', $defaultSite->id)->delete();
        }
    }

    /**
     * Normalize domain to match Site model behavior.
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        $domain = preg_replace('#:\d+$#', '', $domain);

        return $domain;
    }
};
