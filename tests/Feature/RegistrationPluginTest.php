<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;
use Tallcms\Registration\Listeners\AssignDefaultSitePlan;
use Tests\TestCase;

class RegistrationPluginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['registration.enabled' => true]);
        config(['registration.default_role' => 'author']);
        // These tests cover registration mechanics, not the post-registration
        // onboarding redirect introduced in v1.3.2. Disabling onboarding +
        // pinning `redirect_after` keeps them focused and decoupled from
        // multisite + Filament panel-id assumptions.
        config(['registration.onboarding.enabled' => false]);
        config(['registration.redirect_after' => '/registered']);

        Role::findOrCreate('author', 'web');
    }

    // -------------------------------------------------------
    // Form display
    // -------------------------------------------------------

    public function test_register_page_renders(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Create an Account');
    }

    public function test_register_page_returns_404_when_disabled(): void
    {
        config(['registration.enabled' => false]);

        $this->get('/register')->assertNotFound();
    }

    public function test_authenticated_user_is_redirected_from_register(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/register')
            ->assertRedirect(config('registration.redirect_after', '/admin'));
    }

    // -------------------------------------------------------
    // Successful registration
    // -------------------------------------------------------

    public function test_user_can_register(): void
    {
        $response = $this->post('/register/submit', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(url('/registered'));

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('author'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_success_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/registered')
            ->assertOk()
            ->assertSee('Account Created')
            ->assertSee('Set up your first site');
    }

    public function test_success_page_redirects_unauthenticated_user(): void
    {
        // v1.3.2 sends unauthenticated visitors back to the register form,
        // not the configured post-register redirect.
        $this->get('/registered')->assertRedirect('/register');
    }

    // -------------------------------------------------------
    // Validation
    // -------------------------------------------------------

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register/submit', [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertCount(1, User::where('email', 'taken@example.com')->get());
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->post('/register/submit', [
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertNull(User::where('email', 'user@example.com')->first());
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $this->post('/register/submit', [
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different456',
        ])->assertSessionHasErrors('password');
    }

    // -------------------------------------------------------
    // Honeypot
    // -------------------------------------------------------

    public function test_honeypot_returns_fake_success(): void
    {
        $this->post('/register/submit', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            '_honeypot' => 'spambot',
        ])->assertRedirect('/');

        $this->assertNull(User::where('email', 'bot@example.com')->first());
    }

    // -------------------------------------------------------
    // Rate limiting
    // -------------------------------------------------------

    public function test_rate_limiter_blocks_after_threshold(): void
    {
        // Pre-fill the rate limiter to simulate 5 prior registrations
        $key = 'registration:127.0.0.1';
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 60);
        }

        // Next attempt should be rate limited
        $this->post('/register/submit', [
            'name' => 'User Blocked',
            'email' => 'blocked@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertNull(User::where('email', 'blocked@example.com')->first());
    }

    public function test_validation_failures_do_not_burn_rate_limit(): void
    {
        // Submit 5 invalid requests (short password)
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register/submit', [
                'name' => 'User',
                'email' => 'user@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ]);
        }

        // Next valid request should succeed (validation failures didn't count)
        $this->post('/register/submit', [
            'name' => 'Valid User',
            'email' => 'valid@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(url('/registered'));

        $this->assertNotNull(User::where('email', 'valid@example.com')->first());
    }

    // -------------------------------------------------------
    // Missing role (misconfiguration guard)
    // -------------------------------------------------------

    public function test_missing_role_aborts_without_creating_user(): void
    {
        config(['registration.default_role' => 'nonexistent_role']);

        $this->post('/register/submit', [
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(500);

        $this->assertNull(User::where('email', 'user@example.com')->first());
    }

    // -------------------------------------------------------
    // Multisite listener
    // -------------------------------------------------------

    public function test_registration_assigns_default_site_plan_when_multisite_installed(): void
    {
        if (! class_exists(\Tallcms\Multisite\Models\UserSitePlan::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        $this->post('/register/submit', [
            'name' => 'Plan User',
            'email' => 'planuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(url('/registered'));

        $user = User::where('email', 'planuser@example.com')->first();
        $this->assertNotNull($user);

        $assignment = \Tallcms\Multisite\Models\UserSitePlan::where('user_id', $user->id)->first();
        $this->assertNotNull($assignment, 'User should have a site plan assignment after registration.');
    }

    public function test_registration_succeeds_when_multisite_listener_throws(): void
    {
        if (! class_exists(\Tallcms\Multisite\Services\SitePlanService::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        // Bind a SitePlanService that throws
        $this->app->bind(\Tallcms\Multisite\Services\SitePlanService::class, function () {
            return new class {
                public function ensureAssignment($user): never
                {
                    throw new \RuntimeException('Multisite is broken');
                }
            };
        });

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'failed to assign default site plan');
            });

        $this->post('/register/submit', [
            'name' => 'Resilient User',
            'email' => 'resilient@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(url('/registered'));

        $user = User::where('email', 'resilient@example.com')->first();
        $this->assertNotNull($user, 'User should still be created even when multisite listener fails.');
        $this->assertTrue($user->hasRole('author'));
        $this->assertAuthenticatedAs($user);
    }
}
