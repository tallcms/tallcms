<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TlsVerifyEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure multisite config defaults
        config([
            'tallcms.multisite.base_domain' => null,
            'tallcms.multisite.tls_verify_token' => null,
        ]);
    }

    protected function createSite(array $attributes = []): void
    {
        \Illuminate\Support\Facades\DB::table('tallcms_sites')->insert(array_merge([
            'name' => 'Test Site',
            'domain' => 'test.example.com',
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'is_active' => true,
            'is_default' => false,
            'domain_verified' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    public function test_returns_200_for_active_verified_site(): void
    {
        $this->createSite(['domain' => 'mysite.com', 'domain_verified' => true]);

        $response = $this->get('/internal/tls/verify?domain=mysite.com');

        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    public function test_returns_404_for_unknown_domain(): void
    {
        $response = $this->get('/internal/tls/verify?domain=unknown.com');

        $response->assertStatus(404);
    }

    public function test_returns_404_for_inactive_site(): void
    {
        $this->createSite([
            'domain' => 'inactive.com',
            'is_active' => false,
            'domain_verified' => true,
        ]);

        $response = $this->get('/internal/tls/verify?domain=inactive.com');

        $response->assertStatus(404);
    }

    public function test_returns_404_for_unverified_custom_domain(): void
    {
        $this->createSite([
            'domain' => 'custom.com',
            'domain_verified' => false,
        ]);

        $response = $this->get('/internal/tls/verify?domain=custom.com');

        $response->assertStatus(404);
    }

    public function test_returns_200_for_managed_subdomain_even_if_not_verified(): void
    {
        config(['tallcms.multisite.base_domain' => 'yoursaas.com']);

        $this->createSite([
            'domain' => 'tenant.yoursaas.com',
            'domain_verified' => false,
        ]);

        $response = $this->get('/internal/tls/verify?domain=tenant.yoursaas.com');

        $response->assertStatus(200);
    }

    public function test_apex_domain_is_not_auto_trusted(): void
    {
        config(['tallcms.multisite.base_domain' => 'yoursaas.com']);

        $this->createSite([
            'domain' => 'yoursaas.com',
            'domain_verified' => false,
        ]);

        $response = $this->get('/internal/tls/verify?domain=yoursaas.com');

        $response->assertStatus(404);
    }

    public function test_suffix_collision_is_not_trusted(): void
    {
        config(['tallcms.multisite.base_domain' => 'yoursaas.com']);

        $this->createSite([
            'domain' => 'evil-yoursaas.com',
            'domain_verified' => false,
        ]);

        $response = $this->get('/internal/tls/verify?domain=evil-yoursaas.com');

        $response->assertStatus(404);
    }

    public function test_returns_400_for_missing_domain_param(): void
    {
        $response = $this->get('/internal/tls/verify');

        $response->assertStatus(400);
    }

    public function test_returns_401_when_token_required_but_missing(): void
    {
        config(['tallcms.multisite.tls_verify_token' => 'secret-token']);

        $this->createSite(['domain' => 'mysite.com']);

        $response = $this->get('/internal/tls/verify?domain=mysite.com');

        $response->assertStatus(401);
    }

    public function test_returns_200_with_correct_token(): void
    {
        config(['tallcms.multisite.tls_verify_token' => 'secret-token']);

        $this->createSite(['domain' => 'mysite.com', 'domain_verified' => true]);

        $response = $this->get('/internal/tls/verify?domain=mysite.com', [
            'X-Internal-Token' => 'secret-token',
        ]);

        $response->assertStatus(200);
    }

    public function test_returns_401_with_wrong_token(): void
    {
        config(['tallcms.multisite.tls_verify_token' => 'secret-token']);

        $response = $this->get('/internal/tls/verify?domain=mysite.com', [
            'X-Internal-Token' => 'wrong-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_503_when_sites_table_missing(): void
    {
        Schema::dropIfExists('tallcms_sites');

        $response = $this->get('/internal/tls/verify?domain=test.com');

        $response->assertStatus(503);
    }

    public function test_base_domain_config_is_normalized(): void
    {
        config(['tallcms.multisite.base_domain' => '  YourSaaS.COM  ']);

        $this->createSite([
            'domain' => 'tenant.yoursaas.com',
            'domain_verified' => false,
        ]);

        $response = $this->get('/internal/tls/verify?domain=tenant.yoursaas.com');

        $response->assertStatus(200);
    }
}
