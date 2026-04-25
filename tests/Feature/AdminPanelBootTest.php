<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Tests\TestCase;

class AdminPanelBootTest extends TestCase
{
    /**
     * Pins the Filament panel-method signatures we depend on for email
     * verification: `->emailVerification(isRequired: …)` (named) and
     * `->emailChangeVerification(…)` (positional). A future Filament release
     * that renames or changes these signatures will fail panel boot here
     * before it can quietly disable verification in production.
     */
    public function test_admin_panel_boots_with_email_verification_methods_configured(): void
    {
        config()->set('registration.email_verification.enabled', true);

        $panel = Filament::getPanel('admin');

        $this->assertNotNull($panel, 'Admin panel must be registered.');

        // Hitting the panel routes URL forces the panel to fully boot,
        // which is when invalid panel-method signatures would fatal.
        $this->assertNotEmpty($panel->getUrl(), 'Admin panel must produce a URL.');
    }
}
