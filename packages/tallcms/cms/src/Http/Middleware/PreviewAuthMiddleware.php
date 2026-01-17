<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authentication middleware for preview routes.
 *
 * Unlike Laravel's default auth middleware, this doesn't assume a 'login'
 * route exists. It checks for authentication and redirects to a configurable
 * route (defaulting to Filament's login) or returns a 401 response.
 */
class PreviewAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $guard = $guard ?: config('tallcms.auth.guard', 'web');

        if (auth($guard)->check()) {
            return $next($request);
        }

        // Try to redirect to a configured login route
        $loginRoute = $this->getLoginRoute();

        if ($loginRoute) {
            return redirect()->guest($loginRoute);
        }

        // No valid login route found - return 401
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        abort(401, 'Authentication required to preview content.');
    }

    /**
     * Get the login route URL, trying multiple options.
     */
    protected function getLoginRoute(): ?string
    {
        // 1. Check for configured login route in TallCMS config
        $configuredRoute = config('tallcms.auth.login_route');
        if ($configuredRoute) {
            // If it's a route name, resolve it
            if (Route::has($configuredRoute)) {
                return route($configuredRoute);
            }
            // Otherwise treat as URL
            return $configuredRoute;
        }

        // 2. Try Filament's admin panel login route
        $filamentRoute = $this->getFilamentLoginRoute();
        if ($filamentRoute) {
            return $filamentRoute;
        }

        // 3. Try standard Laravel login route
        if (Route::has('login')) {
            return route('login');
        }

        return null;
    }

    /**
     * Get Filament's login route if available.
     */
    protected function getFilamentLoginRoute(): ?string
    {
        // Get the panel ID from config or default to 'admin'
        $panelId = config('tallcms.filament.panel_id', 'admin');

        // Try the panel-specific login route
        $routeName = "filament.{$panelId}.auth.login";
        if (Route::has($routeName)) {
            return route($routeName);
        }

        // Try generic filament login
        if (Route::has('filament.auth.login')) {
            return route('filament.auth.login');
        }

        return null;
    }
}
