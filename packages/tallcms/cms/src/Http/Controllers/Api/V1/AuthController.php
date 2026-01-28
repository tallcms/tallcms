<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use TallCms\Cms\Http\Requests\Api\V1\CreateTokenRequest;

class AuthController extends Controller
{

    /**
     * Create a new API token.
     *
     * This endpoint is public but rate-limited by IP+email hash to prevent
     * brute force attacks while not affecting legitimate users on shared IPs.
     *
     * @unauthenticated
     *
     * @group Authentication
     *
     * @bodyParam email string required The user's email address. Example: user@example.com
     * @bodyParam password string required The user's password. Example: password123
     * @bodyParam device_name string required A name for the token/device. Example: API Client
     * @bodyParam abilities string[] Token abilities. Must be from the allowed list. Example: ["pages:read", "posts:read"]
     * @bodyParam expires_in_days int Optional token expiry in days. Default from config.
     *
     * @response 201 {"data": {"token": "1|abc123...", "expires_at": "2027-01-27T10:30:00Z", "abilities": ["pages:read", "posts:read"]}}
     * @response 401 {"error": {"message": "Invalid credentials", "code": "invalid_credentials"}}
     * @response 429 {"error": {"message": "Too many attempts. Try again in 300 seconds.", "code": "rate_limit_exceeded"}}
     */
    public function store(CreateTokenRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->validated('email')));
        $key = 'api-auth:'.hash('sha256', $request->ip().':'.$email);
        $maxAttempts = (int) config('tallcms.api.auth_rate_limit', 5);
        $lockoutMinutes = (int) config('tallcms.api.auth_lockout_minutes', 15);

        // Check if already locked out
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return $this->respondTooManyRequests(
                $seconds,
                $maxAttempts,
                "Too many attempts. Try again in {$seconds} seconds."
            );
        }

        // Find the user
        $userModel = config('tallcms.plugin_mode.user_model', 'App\\Models\\User');
        $user = $userModel::where('email', $email)->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            RateLimiter::hit($key, $lockoutMinutes * 60);
            $remaining = RateLimiter::remaining($key, $maxAttempts);

            // If this was the last attempt, return 429 immediately
            if ($remaining <= 0) {
                $seconds = RateLimiter::availableIn($key);

                return $this->respondTooManyRequests(
                    $seconds,
                    $maxAttempts,
                    "Too many attempts. Try again in {$seconds} seconds."
                );
            }

            return response()->json([
                'error' => [
                    'message' => 'Invalid credentials',
                    'code' => 'invalid_credentials',
                ],
            ], 401, [
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => $remaining,
            ]);
        }

        // Clear rate limit on successful auth
        RateLimiter::clear($key);

        // Get validated abilities (required field, already validated by FormRequest)
        $abilities = $request->validated('abilities');

        // Calculate expiry
        $expiresInDays = $request->validated('expires_in_days', config('tallcms.api.token_expiry_days', 365));
        $expiresAt = now()->addDays($expiresInDays);

        // Create the token
        $token = $user->createToken(
            $request->validated('device_name'),
            $abilities,
            $expiresAt
        );

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'expires_at' => $expiresAt->toIso8601String(),
                'abilities' => $abilities,
            ],
        ], 201, [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $maxAttempts, // Reset after success
        ]);
    }

    /**
     * Revoke the current token.
     *
     * @authenticated
     *
     * @group Authentication
     *
     * @response 200 {"message": "Token revoked successfully"}
     * @response 400 {"error": {"message": "No token to revoke", "code": "no_token"}}
     */
    public function destroy(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if (! $token) {
            return $this->respondWithError('No token to revoke', 'no_token', 400);
        }

        $token->delete();

        return $this->respondWithMessage('Token revoked successfully');
    }

    /**
     * Get the authenticated user.
     *
     * @authenticated
     *
     * @group Authentication
     *
     * @response 200 {"data": {"id": 1, "name": "John Doe", "email": "john@example.com", "token": {...}}}
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];

        // Only include token info when authenticated via a real PersonalAccessToken
        // TransientToken (from Sanctum::actingAs) returns false for expires_at/last_used_at
        if ($token && $token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            // Defensive check: expires_at/last_used_at may be false in some edge cases
            $expiresAt = $token->expires_at;
            $lastUsedAt = $token->last_used_at;

            $data['token'] = [
                'name' => $token->name,
                'abilities' => $token->abilities,
                'expires_at' => ($expiresAt && $expiresAt !== false) ? $expiresAt->toIso8601String() : null,
                'last_used_at' => ($lastUsedAt && $lastUsedAt !== false) ? $lastUsedAt->toIso8601String() : null,
            ];
        }

        return $this->respondWithData($data);
    }
}
