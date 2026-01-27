<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use TallCms\Cms\Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tallcms.api.enabled' => true]);
    }

    protected function createUser(array $attributes = []): object
    {
        $userModel = config('tallcms.plugin_mode.user_model', 'App\\Models\\User');

        return $userModel::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ], $attributes));
    }

    public function test_can_create_token_with_valid_credentials(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
            'abilities' => ['pages:read', 'posts:read'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['token', 'expires_at', 'abilities'],
            ])
            ->assertJsonPath('data.abilities', ['pages:read', 'posts:read']);
    }

    public function test_token_creation_fails_with_invalid_credentials(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device',
            'abilities' => ['pages:read'],
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'invalid_credentials');
    }

    public function test_token_creation_validates_abilities(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
            'abilities' => ['invalid:ability'],
        ]);

        $response->assertStatus(422);
    }

    public function test_token_creation_requires_abilities(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
            // abilities intentionally omitted
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['abilities']);
    }

    public function test_token_creation_requires_non_empty_abilities(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
            'abilities' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['abilities']);
    }

    public function test_rate_limiting_locks_after_max_attempts(): void
    {
        $user = $this->createUser();
        $maxAttempts = config('tallcms.api.auth_rate_limit', 5);

        // Make max attempts with wrong password
        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = $this->postJson('/api/v1/tallcms/auth/token', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
                'device_name' => 'Test Device',
                'abilities' => ['pages:read'],
            ]);
        }

        // Next attempt should be rate limited
        $response = $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device',
            'abilities' => ['pages:read'],
        ]);

        $response->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_successful_auth_clears_rate_limit(): void
    {
        $user = $this->createUser();

        // Make a failed attempt
        $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device',
            'abilities' => ['pages:read'],
        ]);

        // Successful auth
        $response = $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
            'abilities' => ['pages:read'],
        ]);

        $response->assertStatus(201);

        // Should have full quota restored
        $maxAttempts = config('tallcms.api.auth_rate_limit', 5);
        $response->assertHeader('X-RateLimit-Remaining', $maxAttempts);
    }

    public function test_can_revoke_token(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/v1/tallcms/auth/token');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Token revoked successfully');
    }

    public function test_can_get_authenticated_user(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user, ['pages:read']);

        $response = $this->getJson('/api/v1/tallcms/auth/user');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'test@example.com')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'token'],
            ]);
    }

    public function test_expired_token_returns_401(): void
    {
        $user = $this->createUser();

        // Create a token that's already expired
        $token = $user->createToken('test', ['*'], now()->subDay());

        $response = $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->getJson('/api/v1/tallcms/auth/user');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'token_expired');
    }

    public function test_email_normalization_for_rate_limiting(): void
    {
        $user = $this->createUser();

        // First attempt with normal email
        $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device',
            'abilities' => ['pages:read'],
        ]);

        // Second attempt with uppercase - should count against same limit
        $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device',
            'abilities' => ['pages:read'],
        ]);

        // Third attempt with spaces - should count against same limit
        $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => '  test@example.com  ',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device',
            'abilities' => ['pages:read'],
        ]);

        // All should count as same user, so we should have used 3 attempts
        $response = $this->postJson('/api/v1/tallcms/auth/token', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device',
            'abilities' => ['pages:read'],
        ]);

        $maxAttempts = config('tallcms.api.auth_rate_limit', 5);
        $this->assertTrue($response->headers->get('X-RateLimit-Remaining') <= $maxAttempts - 4);
    }
}
