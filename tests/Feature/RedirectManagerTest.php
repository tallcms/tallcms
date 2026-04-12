<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tallcms\RedirectManager\Models\Redirect;
use Tallcms\RedirectManager\Rules\NoSelfRedirect;
use Tests\TestCase;

class RedirectManagerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // Path normalization
    // -------------------------------------------------------

    public function test_normalize_path_ensures_leading_slash(): void
    {
        $this->assertEquals('/page', Redirect::normalizePath('page'));
    }

    public function test_normalize_path_strips_trailing_slash(): void
    {
        $this->assertEquals('/page', Redirect::normalizePath('/page/'));
    }

    public function test_normalize_path_preserves_root(): void
    {
        $this->assertEquals('/', Redirect::normalizePath('/'));
    }

    public function test_normalize_path_preserves_case(): void
    {
        $this->assertEquals('/Old-Page', Redirect::normalizePath('/Old-Page'));
    }

    // -------------------------------------------------------
    // Model: canonical storage
    // -------------------------------------------------------

    public function test_source_path_is_canonicalized_on_create(): void
    {
        $redirect = Redirect::create([
            'source_path' => 'old-page/',
            'destination_url' => '/new-page',
            'status_code' => 301,
        ]);

        $this->assertEquals('/old-page', $redirect->source_path);
        $this->assertEquals(hash('sha256', '/old-page'), $redirect->source_path_hash);
    }

    public function test_source_path_hash_enforces_uniqueness(): void
    {
        Redirect::create([
            'source_path' => '/old-page',
            'destination_url' => '/new-page',
            'status_code' => 301,
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Redirect::create([
            'source_path' => '/old-page/',
            'destination_url' => '/other-page',
            'status_code' => 302,
        ]);
    }

    // -------------------------------------------------------
    // Model: cache invalidation
    // -------------------------------------------------------

    public function test_cache_is_invalidated_on_create(): void
    {
        Cache::put('tallcms.redirects', ['stale' => true]);

        Redirect::create([
            'source_path' => '/test',
            'destination_url' => '/dest',
            'status_code' => 301,
        ]);

        $this->assertNull(Cache::get('tallcms.redirects'));
    }

    public function test_cache_is_invalidated_on_update(): void
    {
        $redirect = Redirect::create([
            'source_path' => '/test',
            'destination_url' => '/dest',
            'status_code' => 301,
        ]);

        Cache::put('tallcms.redirects', ['stale' => true]);

        $redirect->update(['destination_url' => '/new-dest']);

        $this->assertNull(Cache::get('tallcms.redirects'));
    }

    public function test_cache_is_invalidated_on_delete(): void
    {
        $redirect = Redirect::create([
            'source_path' => '/test',
            'destination_url' => '/dest',
            'status_code' => 301,
        ]);

        Cache::put('tallcms.redirects', ['stale' => true]);

        $redirect->delete();

        $this->assertNull(Cache::get('tallcms.redirects'));
    }

    // -------------------------------------------------------
    // Middleware: redirects
    // -------------------------------------------------------

    public function test_middleware_redirects_matching_path(): void
    {
        Redirect::create([
            'source_path' => '/old',
            'destination_url' => '/new',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/old')
            ->assertRedirect('/new')
            ->assertStatus(301);
    }

    public function test_middleware_handles_302_redirect(): void
    {
        Redirect::create([
            'source_path' => '/temp',
            'destination_url' => '/other',
            'status_code' => 302,
            'is_active' => true,
        ]);

        $this->get('/temp')
            ->assertRedirect('/other')
            ->assertStatus(302);
    }

    public function test_middleware_ignores_inactive_redirects(): void
    {
        Redirect::create([
            'source_path' => '/inactive',
            'destination_url' => '/dest',
            'status_code' => 301,
            'is_active' => false,
        ]);

        $this->get('/inactive')
            ->assertStatus(404);
    }

    public function test_middleware_matches_with_trailing_slash(): void
    {
        Redirect::create([
            'source_path' => '/old',
            'destination_url' => '/new',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/old/')
            ->assertRedirect('/new')
            ->assertStatus(301);
    }

    public function test_middleware_ignores_post_requests(): void
    {
        Redirect::create([
            'source_path' => '/form',
            'destination_url' => '/new-form',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->post('/form')
            ->assertStatus(405); // Method not allowed or similar — not a redirect
    }

    public function test_middleware_passes_through_unmatched_paths(): void
    {
        Redirect::create([
            'source_path' => '/old',
            'destination_url' => '/new',
            'status_code' => 301,
            'is_active' => true,
        ]);

        // Homepage should still work
        $this->get('/')
            ->assertStatus(200);
    }

    // -------------------------------------------------------
    // Middleware: hit tracking
    // -------------------------------------------------------

    public function test_middleware_increments_hit_count(): void
    {
        $redirect = Redirect::create([
            'source_path' => '/tracked',
            'destination_url' => '/dest',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/tracked');
        $this->get('/tracked');

        $redirect->refresh();
        $this->assertEquals(2, $redirect->hit_count);
        $this->assertNotNull($redirect->last_hit_at);
    }

    // -------------------------------------------------------
    // Middleware: redirect to full URL
    // -------------------------------------------------------

    public function test_middleware_redirects_to_full_url(): void
    {
        Redirect::create([
            'source_path' => '/external',
            'destination_url' => 'https://example.com/page',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/external')
            ->assertRedirect('https://example.com/page');
    }

    // -------------------------------------------------------
    // Self-redirect prevention: middleware safety
    // -------------------------------------------------------

    public function test_middleware_skips_self_redirect(): void
    {
        // Bypass model-level validation by inserting directly
        Redirect::query()->insert([
            'source_path' => '/loop',
            'source_path_hash' => hash('sha256', '/loop'),
            'destination_url' => '/loop',
            'status_code' => 301,
            'is_active' => true,
            'hit_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Should pass through, not infinite redirect
        $this->get('/loop')
            ->assertStatus(404);
    }

    public function test_middleware_skips_self_redirect_with_trailing_slash(): void
    {
        Redirect::query()->insert([
            'source_path' => '/loop',
            'source_path_hash' => hash('sha256', '/loop'),
            'destination_url' => '/loop/',
            'status_code' => 301,
            'is_active' => true,
            'hit_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/loop')
            ->assertStatus(404);
    }

    public function test_middleware_skips_self_redirect_with_absolute_same_host_url(): void
    {
        $appUrl = config('app.url', 'http://localhost');

        Redirect::query()->insert([
            'source_path' => '/loop',
            'source_path_hash' => hash('sha256', '/loop'),
            'destination_url' => $appUrl.'/loop',
            'status_code' => 301,
            'is_active' => true,
            'hit_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/loop')
            ->assertStatus(404);
    }

    // -------------------------------------------------------
    // Self-redirect prevention: validation rule
    // -------------------------------------------------------

    public function test_no_self_redirect_rule_rejects_same_path(): void
    {
        $validator = Validator::make(
            ['source_path' => '/old', 'destination_url' => '/old'],
            ['destination_url' => [new NoSelfRedirect]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('infinite redirect', $validator->errors()->first('destination_url'));
    }

    public function test_no_self_redirect_rule_rejects_trailing_slash_variant(): void
    {
        $validator = Validator::make(
            ['source_path' => '/old', 'destination_url' => '/old/'],
            ['destination_url' => [new NoSelfRedirect]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_no_self_redirect_rule_allows_different_paths(): void
    {
        $validator = Validator::make(
            ['source_path' => '/old', 'destination_url' => '/new'],
            ['destination_url' => [new NoSelfRedirect]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_no_self_redirect_rule_allows_external_url(): void
    {
        $validator = Validator::make(
            ['source_path' => '/page', 'destination_url' => 'https://other-site.com/page'],
            ['destination_url' => [new NoSelfRedirect]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_no_self_redirect_rule_catches_same_host_absolute_url(): void
    {
        $appUrl = config('app.url', 'http://localhost');

        $validator = Validator::make(
            ['source_path' => '/page', 'destination_url' => $appUrl.'/page'],
            ['destination_url' => [new NoSelfRedirect]]
        );

        $this->assertTrue($validator->fails());
    }

    // -------------------------------------------------------
    // Filament admin pages render without errors
    // -------------------------------------------------------

    public function test_admin_redirect_list_page_renders(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('super_admin', 'web');
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get('/admin/redirects')
            ->assertSuccessful();
    }

    public function test_admin_redirect_create_page_renders(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('super_admin', 'web');
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get('/admin/redirects/create')
            ->assertSuccessful();
    }

    public function test_admin_redirect_edit_page_renders(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('super_admin', 'web');
        $user->assignRole('super_admin');

        $redirect = Redirect::create([
            'source_path' => '/edit-test',
            'destination_url' => '/dest',
            'status_code' => 301,
        ]);

        $this->actingAs($user)
            ->get("/admin/redirects/{$redirect->id}/edit")
            ->assertSuccessful();
    }
}
