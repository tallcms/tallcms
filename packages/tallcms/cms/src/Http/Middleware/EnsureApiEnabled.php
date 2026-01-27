<?php

namespace TallCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiEnabled
{
    /**
     * Handle an incoming request.
     *
     * Aborts with 404 if the API is disabled.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('tallcms.api.enabled', false)) {
            abort(404);
        }

        return $next($request);
    }
}
