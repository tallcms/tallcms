<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Support\InstallationStatus;

class MaintenanceModeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip maintenance mode check if installation is not complete
        if (InstallationStatus::isIncomplete()) {
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
}
