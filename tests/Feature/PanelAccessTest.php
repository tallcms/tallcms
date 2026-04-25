<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function getPanel(): \Filament\Panel
    {
        return Filament::getPanel('admin');
    }

    // -------------------------------------------------------
    // Active/Inactive Users
    // -------------------------------------------------------

    public function test_inactive_user_cannot_access_panel(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        Role::findOrCreate('editor');
        $user->assignRole('editor');

        $this->assertFalse($user->canAccessPanel($this->getPanel()));
    }

    public function test_active_user_with_role_can_access_panel(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Role::findOrCreate('editor');
        $user->assignRole('editor');

        $this->assertTrue($user->canAccessPanel($this->getPanel()));
    }

    // -------------------------------------------------------
    // First User Bootstrap
    // -------------------------------------------------------

    public function test_first_user_can_access_panel_without_role(): void
    {
        // Ensure only one user exists
        User::query()->delete();
        $user = User::factory()->create();

        $this->assertTrue($user->isFirstUser());
        $this->assertTrue($user->canAccessPanel($this->getPanel()));
    }

    public function test_second_user_without_role_cannot_access_panel(): void
    {
        User::factory()->create();
        $second = User::factory()->create();

        $this->assertFalse($second->isFirstUser());
        $this->assertFalse($second->canAccessPanel($this->getPanel()));
    }

    // -------------------------------------------------------
    // Role-Based Access (any role works)
    // -------------------------------------------------------

    public function test_user_with_any_role_can_access_panel(): void
    {
        User::factory()->create(); // ensure not first user
        $user = User::factory()->create();

        Role::findOrCreate('custom_role');
        $user->assignRole('custom_role');

        $this->assertTrue($user->canAccessPanel($this->getPanel()));
    }

    public function test_user_without_role_cannot_access_panel(): void
    {
        User::factory()->create(); // ensure not first user
        $user = User::factory()->create();

        $this->assertFalse($user->canAccessPanel($this->getPanel()));
    }

    public function test_inactive_user_with_role_still_blocked(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        Role::findOrCreate('super_admin');
        $user->assignRole('super_admin');

        $this->assertFalse($user->canAccessPanel($this->getPanel()));
    }

    // -------------------------------------------------------
    // Email Verification Gate (REGISTRATION_EMAIL_VERIFICATION)
    // -------------------------------------------------------

    public function test_unverified_user_is_rejected_when_flag_on(): void
    {
        config()->set('registration.email_verification.enabled', true);

        User::factory()->create(); // ensure not first user
        $user = User::factory()->unverified()->create();
        Role::findOrCreate('editor');
        $user->assignRole('editor');

        $this->assertFalse($user->canAccessPanel($this->getPanel()));
    }

    public function test_unverified_user_is_allowed_when_flag_off(): void
    {
        config()->set('registration.email_verification.enabled', false);

        User::factory()->create(); // ensure not first user
        $user = User::factory()->unverified()->create();
        Role::findOrCreate('editor');
        $user->assignRole('editor');

        $this->assertTrue($user->canAccessPanel($this->getPanel()));
    }

    public function test_first_user_bypasses_verification_gate_via_role_safety_net(): void
    {
        // Setup marks the first user verified at install time, but the
        // safety-net early-return in canAccessPanel covers the role-less
        // case on a fresh install regardless. This test pins that contract.
        config()->set('registration.email_verification.enabled', true);

        User::query()->delete();
        $user = User::factory()->unverified()->create();

        $this->assertTrue($user->isFirstUser());
        $this->assertTrue($user->canAccessPanel($this->getPanel()));
    }

    public function test_verified_user_with_role_allowed_in_both_modes(): void
    {
        User::factory()->create(); // ensure not first user
        $user = User::factory()->create(); // verified by factory default
        Role::findOrCreate('editor');
        $user->assignRole('editor');

        config()->set('registration.email_verification.enabled', false);
        $this->assertTrue($user->canAccessPanel($this->getPanel()));

        config()->set('registration.email_verification.enabled', true);
        $this->assertTrue($user->canAccessPanel($this->getPanel()));
    }
}
