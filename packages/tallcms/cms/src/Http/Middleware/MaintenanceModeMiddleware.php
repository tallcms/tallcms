<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use TallCms\Cms\Models\SiteSetting;

class MaintenanceModeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip maintenance mode check if installation is not complete
        if ($this->installationIncomplete()) {
            return $next($request);
        }

        // Get admin panel path from config (defaults to 'admin')
        $panelPath = config('tallcms.filament.panel_path', 'admin');

        // Skip maintenance mode for admin and installer routes
        if ($request->is("{$panelPath}*") || $request->is($panelPath) || $request->is('install*')) {
            return $next($request);
        }

        try {
            // Check if maintenance mode is enabled
            $maintenanceMode = SiteSetting::get('maintenance_mode', false);

            if ($maintenanceMode) {
                $maintenanceMessage = SiteSetting::get('maintenance_message', 'We\'re currently performing scheduled maintenance. Please check back soon!');
                $siteName = SiteSetting::get('site_name');

                return response()->view('tallcms::maintenance', [
                    'maintenanceMessage' => $maintenanceMessage,
                    'siteName' => $siteName,
                ], 503);
            }
        } catch (\Exception $e) {
            // If database query fails, skip maintenance check
            // This handles cases where tables don't exist yet
        }

        return $next($request);
    }

    /**
     * Check if installation is incomplete
     */
    private function installationIncomplete(): bool
    {
        // In plugin mode, skip installer.lock check if configured
        // (host app doesn't use TallCMS's installer)
        $skipInstallerCheck = config('tallcms.plugin_mode.skip_installer_check', true);
        $isPluginMode = config('tallcms.mode') === 'plugin' ||
            (config('tallcms.mode') === null && ! File::exists(base_path('.tallcms-standalone')));

        if ($isPluginMode && $skipInstallerCheck) {
            // In plugin mode, only check if database tables exist
            try {
                return ! Schema::hasTable((new SiteSetting)->getTable());
            } catch (\Exception $e) {
                // If we can't check the schema, skip maintenance mode
                return true;
            }
        }

        // Standalone mode: full installation checks
        // Installation is incomplete if:
        // 1. No installer lock file exists
        // 2. Database tables don't exist
        // 3. .env doesn't exist

        if (! File::exists(storage_path('installer.lock'))) {
            return true;
        }

        if (! File::exists(base_path('.env'))) {
            return true;
        }

        try {
            // Check if database is configured
            if (empty(config('database.connections.'.config('database.default').'.database'))) {
                return true;
            }

            // Check if the settings table exists using the model's table name
            return ! Schema::hasTable((new SiteSetting)->getTable());
        } catch (\Exception $e) {
            // If we can't check the schema, assume installation is incomplete
            // This handles cases where database isn't configured or accessible
            return true;
        }
    }
}
