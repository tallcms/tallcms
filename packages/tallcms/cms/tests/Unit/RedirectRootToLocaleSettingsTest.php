<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use TallCms\Cms\Filament\Resources\SiteResource\SiteForm;
use TallCms\Cms\Models\Site;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\SiteSettingsService;
use TallCms\Cms\Tests\TestCase;

class RedirectRootToLocaleSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_is_not_applicable_when_hide_default_locale_is_on(): void
    {
        SiteSetting::setGlobal('i18n_enabled', true, 'boolean', 'i18n');
        SiteSetting::setGlobal('hide_default_locale', true, 'boolean', 'i18n');
        SiteSetting::clearCache();

        $this->assertFalse(SiteForm::isRedirectRootToLocaleApplicable());
        $this->assertFalse(SiteForm::normalizeRedirectRootToLocaleFormValue(true));
    }

    public function test_redirect_is_applicable_when_i18n_on_and_default_locale_visible(): void
    {
        SiteSetting::setGlobal('i18n_enabled', true, 'boolean', 'i18n');
        SiteSetting::setGlobal('hide_default_locale', false, 'boolean', 'i18n');
        SiteSetting::clearCache();

        $this->assertTrue(SiteForm::isRedirectRootToLocaleApplicable());
        $this->assertTrue(SiteForm::normalizeRedirectRootToLocaleFormValue(true));
    }

    public function test_reset_all_sites_for_key_removes_redirect_overrides(): void
    {
        $site = Site::query()->create([
            'name' => 'Second',
            'domain' => 'second.test',
            'is_default' => false,
            'is_active' => true,
        ]);

        $service = app(SiteSettingsService::class);
        $service->setForSite($site->id, 'redirect_root_to_locale', true, 'boolean');

        $this->assertTrue($service->hasOverride($site->id, 'redirect_root_to_locale'));

        $service->resetAllSitesForKey('redirect_root_to_locale');

        $this->assertFalse($service->hasOverride($site->id, 'redirect_root_to_locale'));
    }
}
