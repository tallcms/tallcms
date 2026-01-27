<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenAbilities
{
    /**
     * Handle an incoming request.
     *
     * Check if the current access token has the required ability.
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => [
                    'message' => 'Unauthenticated',
                    'code' => 'unauthenticated',
                ],
            ], 401);
        }

        $token = $user->currentAccessToken();

        if (! $token) {
            return response()->json([
                'error' => [
                    'message' => 'Unauthenticated',
                    'code' => 'unauthenticated',
                ],
            ], 401);
        }

        // Check if the token has the required ability
        if (! $token->can($ability)) {
            return response()->json([
                'error' => [
                    'message' => 'Token missing required ability: '.$ability,
                    'code' => 'insufficient_abilities',
                ],
            ], 403);
        }

        return $next($request);
    }
}
