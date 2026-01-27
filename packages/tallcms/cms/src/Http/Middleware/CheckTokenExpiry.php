<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiry
{
    /**
     * Handle an incoming request.
     *
     * Check if the current access token has expired and reject if so.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $token = $user->currentAccessToken();

        if (! $token) {
            return $next($request);
        }

        // Check if the token has an expiry and if it has passed
        if ($token->expires_at && $token->expires_at->isPast()) {
            // Delete the expired token
            $token->delete();

            return response()->json([
                'error' => [
                    'message' => 'Token expired',
                    'code' => 'token_expired',
                ],
            ], 401);
        }

        return $next($request);
    }
}
