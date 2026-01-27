<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use TallCms\Cms\Tests\TestCase;

class QueryParametersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tallcms.api.enabled' => true]);
    }

    protected function createUser(): object
    {
        $userModel = config('tallcms.plugin_mode.user_model', 'App\\Models\\User');

        return $userModel::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    protected function actingAsApiUser(array $abilities = ['*']): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user, $abilities);

        // Mock the policy to allow access
        $this->mock(\TallCms\Cms\Policies\CmsPagePolicy::class, function ($mock) {
            $mock->shouldReceive('viewAny')->andReturn(true);
        });
    }

    public function test_unknown_filter_field_returns_400(): void
    {
        $this->actingAsApiUser(['pages:read']);

        $response = $this->getJson('/api/v1/tallcms/pages?filter[unknown_field]=value');

        $response->assertStatus(400);
    }

    public function test_allowed_filter_fields_work(): void
    {
        $this->actingAsApiUser(['pages:read']);

        // These should not return 400
        $response = $this->getJson('/api/v1/tallcms/pages?filter[status]=published');
        $this->assertNotEquals(400, $response->status());

        $response = $this->getJson('/api/v1/tallcms/pages?filter[author_id]=1');
        $this->assertNotEquals(400, $response->status());
    }

    public function test_unknown_sort_field_returns_400(): void
    {
        $this->actingAsApiUser(['pages:read']);

        $response = $this->getJson('/api/v1/tallcms/pages?sort=unknown_field');

        $response->assertStatus(400);
    }

    public function test_allowed_sort_fields_work(): void
    {
        $this->actingAsApiUser(['pages:read']);

        $response = $this->getJson('/api/v1/tallcms/pages?sort=created_at&order=desc');
        $this->assertNotEquals(400, $response->status());

        $response = $this->getJson('/api/v1/tallcms/pages?sort=title&order=asc');
        $this->assertNotEquals(400, $response->status());
    }

    public function test_unknown_include_returns_400(): void
    {
        $this->actingAsApiUser(['pages:read']);

        $response = $this->getJson('/api/v1/tallcms/pages?include=unknown_relation');

        $response->assertStatus(400);
    }

    public function test_allowed_includes_work(): void
    {
        $this->actingAsApiUser(['pages:read']);

        $response = $this->getJson('/api/v1/tallcms/pages?include=author');
        $this->assertNotEquals(400, $response->status());

        $response = $this->getJson('/api/v1/tallcms/pages?include=parent,children');
        $this->assertNotEquals(400, $response->status());
    }

    public function test_unknown_with_counts_returns_400(): void
    {
        $this->actingAsApiUser(['pages:read']);

        $response = $this->getJson('/api/v1/tallcms/pages?with_counts=unknown_count');

        $response->assertStatus(400);
    }

    public function test_allowed_with_counts_work(): void
    {
        $this->actingAsApiUser(['pages:read']);

        $response = $this->getJson('/api/v1/tallcms/pages?with_counts=children');
        $this->assertNotEquals(400, $response->status());
    }

    public function test_per_page_is_capped_at_max(): void
    {
        $this->actingAsApiUser(['pages:read']);

        $response = $this->getJson('/api/v1/tallcms/pages?per_page=500');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(
            config('tallcms.api.max_per_page', 100),
            $response->json('meta.per_page')
        );
    }

    public function test_pagination_metadata_is_correct(): void
    {
        $this->actingAsApiUser(['pages:read']);

        $response = $this->getJson('/api/v1/tallcms/pages?page=1&per_page=15');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
    }
}
