<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use TallCms\Cms\Tests\TestCase;

class TokenAbilitiesTest extends TestCase
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

    public function test_token_with_read_ability_can_read(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user, ['pages:read']);

        // Mock the policy to allow access
        $this->mock(\TallCms\Cms\Policies\CmsPagePolicy::class, function ($mock) {
            $mock->shouldReceive('viewAny')->andReturn(true);
        });

        $response = $this->getJson('/api/v1/tallcms/pages');

        // Should not get 403 for ability check
        $this->assertNotEquals(403, $response->status());
    }

    public function test_token_without_ability_is_forbidden(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user, ['posts:read']); // Only posts, not pages

        $response = $this->getJson('/api/v1/tallcms/pages');

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'insufficient_abilities');
    }

    public function test_token_with_write_ability_can_create(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user, ['pages:write']);

        // Mock the policy to allow access
        $this->mock(\TallCms\Cms\Policies\CmsPagePolicy::class, function ($mock) {
            $mock->shouldReceive('create')->andReturn(true);
        });

        $response = $this->postJson('/api/v1/tallcms/pages', [
            'title' => 'Test Page',
        ]);

        // Should not get 403 for ability check
        $this->assertNotEquals(403, $response->status());
    }

    public function test_token_without_write_ability_cannot_create(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user, ['pages:read']); // Only read, not write

        $response = $this->postJson('/api/v1/tallcms/pages', [
            'title' => 'Test Page',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'insufficient_abilities');
    }

    public function test_token_with_delete_ability_can_delete(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user, ['pages:delete']);

        // Mock the policy to allow access
        $this->mock(\TallCms\Cms\Policies\CmsPagePolicy::class, function ($mock) {
            $mock->shouldReceive('delete')->andReturn(true);
        });

        $response = $this->deleteJson('/api/v1/tallcms/pages/1');

        // Should not get 403 for ability check (might get 404 for non-existent page)
        $this->assertNotEquals(403, $response->status());
    }

    public function test_wildcard_ability_grants_all_access(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user, ['*']);

        // Mock the policy to allow access
        $this->mock(\TallCms\Cms\Policies\CmsPagePolicy::class, function ($mock) {
            $mock->shouldReceive('viewAny')->andReturn(true);
            $mock->shouldReceive('create')->andReturn(true);
        });

        // Should be able to read
        $readResponse = $this->getJson('/api/v1/tallcms/pages');
        $this->assertNotEquals(403, $readResponse->status());

        // Should be able to write
        $writeResponse = $this->postJson('/api/v1/tallcms/pages', [
            'title' => 'Test Page',
        ]);
        $this->assertNotEquals(403, $writeResponse->status());
    }
}
