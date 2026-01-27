<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    /**
     * Return a successful response with data.
     *
     * @param  mixed  $data
     */
    protected function respondWithData($data, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json(['data' => $data], $status, $headers);
    }

    /**
     * Return a successful response with a message.
     */
    protected function respondWithMessage(string $message, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json(['message' => $message], $status, $headers);
    }

    /**
     * Return an error response.
     */
    protected function respondWithError(string $message, string $code, int $status = 400, array $headers = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'message' => $message,
                'code' => $code,
            ],
        ], $status, $headers);
    }

    /**
     * Return a not found response.
     */
    protected function respondNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->respondWithError($message, 'not_found', 404);
    }

    /**
     * Return a forbidden response.
     */
    protected function respondForbidden(string $message = 'Access denied'): JsonResponse
    {
        return $this->respondWithError($message, 'forbidden', 403);
    }

    /**
     * Return an unauthorized response.
     */
    protected function respondUnauthorized(string $message = 'Unauthenticated'): JsonResponse
    {
        return $this->respondWithError($message, 'unauthenticated', 401);
    }

    /**
     * Return a validation error response.
     *
     * @param  array<string, array<string>>  $errors
     */
    protected function respondValidationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return response()->json([
            'error' => [
                'message' => $message,
                'code' => 'validation_error',
                'errors' => $errors,
            ],
        ], 422);
    }

    /**
     * Return a rate limit exceeded response.
     */
    protected function respondTooManyRequests(int $retryAfter, int $limit, string $message = 'Too many requests'): JsonResponse
    {
        return response()->json([
            'error' => [
                'message' => $message,
                'code' => 'rate_limit_exceeded',
            ],
        ], 429, [
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => 0,
            'Retry-After' => $retryAfter,
        ]);
    }

    /**
     * Return a no content response.
     */
    protected function respondNoContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a created response with data.
     *
     * @param  mixed  $data
     */
    protected function respondCreated($data, array $headers = []): JsonResponse
    {
        return $this->respondWithData($data, 201, $headers);
    }
}
