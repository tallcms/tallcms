<?php

namespace App\Http\Middleware;

use App\Models\CmsPage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HomepageRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only handle root path requests
        if ($request->is('/')) {
            $homepage = CmsPage::getHomepage();
            
            if ($homepage) {
                // Redirect to the homepage using the page renderer
                return redirect()->route('cms.page', $homepage->slug);
            }
            
            // If no homepage is set, continue to default welcome page
            return $next($request);
        }
        
        return $next($request);
    }
}
