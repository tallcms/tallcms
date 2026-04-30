<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Tallcms\FilamentRegistration\Filament\Pages\RegistrationSettings as BasePage;

/**
 * Host-defined Shield-gated subclass of the package's registration settings.
 *
 * The package ships a deliberately Shield-agnostic page so it doesn't force
 * every consumer to depend on bezhansalleh/filament-shield. We add the trait
 * here in the host app and swap this subclass in via
 * Tallcms\Registration\Filament\RegistrationPlugin::settingsPage(), which
 * forwards to FilamentRegistrationPlugin::settingsPage() (added in package
 * v1.2.0).
 *
 * Permission: View:RegistrationSettings (auto-discovered by Shield's
 * generate command from this class name).
 */
class RegistrationSettings extends BasePage
{
    use HasPageShield;
}
