<?php

namespace TallCms\Cms\Tests\Feature;

use TallCms\Cms\Filament\Pages\MenuItemsManager;
use TallCms\Cms\Models\TallcmsMenuItem;
use TallCms\Cms\Tests\TestCase;

/**
 * Regression tests for PR #56 — menu item tree labels must respect the
 * admin's active locale, not the app's default locale.
 *
 * The bug: `label` is a Spatie translatable JSON column, but the
 * NestedsetPage parent class's default `getRecordLabel()` reads
 * `$record->label`, which resolves through Spatie's accessor using the
 * app's current locale — independent of the page's `$activeLocale`
 * state. Result: switching the locale in the header did nothing to the
 * tree view labels.
 *
 * The fix: override `getRecordLabel()` to call `getTranslation()` with
 * the explicit `$activeLocale` and a fallback flag.
 */
class MenuItemsManagerLocaleTest extends TestCase
{
    public function test_record_label_reflects_active_locale(): void
    {
        config(['tallcms.i18n.enabled' => true]);

        $item = new TallcmsMenuItem;
        $item->setTranslation('label', 'en', 'Home');
        $item->setTranslation('label', 'zh_CN', '首页');

        $page = new MenuItemsManager;

        $page->activeLocale = 'en';
        $this->assertSame('Home', (string) $page->getRecordLabel($item));

        $page->activeLocale = 'zh_CN';
        $this->assertSame('首页', (string) $page->getRecordLabel($item),
            'Switching activeLocale must change the label returned to the tree view.');
    }

    public function test_record_label_falls_back_when_locale_translation_is_missing(): void
    {
        config(['tallcms.i18n.enabled' => true]);

        $item = new TallcmsMenuItem;
        $item->setTranslation('label', 'en', 'Home');
        // No zh_CN translation — deliberately missing.

        $page = new MenuItemsManager;
        $page->activeLocale = 'zh_CN';

        // Spatie's getTranslation(..., useFallbackLocale: true) must kick in,
        // so the tree row stays readable instead of showing a blank node.
        $this->assertSame('Home', (string) $page->getRecordLabel($item));
    }

    public function test_record_label_returns_single_space_when_translation_is_empty(): void
    {
        // Matches the NestedsetPage parent's " " convention so the tree row
        // stays clickable even when the record has no usable label at all.
        $item = new TallcmsMenuItem;
        $item->setTranslation('label', 'en', '');

        $page = new MenuItemsManager;
        $page->activeLocale = 'en';

        $this->assertSame(' ', (string) $page->getRecordLabel($item));
    }

    public function test_record_label_uses_default_locale_when_active_locale_is_null(): void
    {
        // mount() always initialises activeLocale, but guard against
        // callers that instantiate the page without mounting.
        config(['tallcms.i18n.enabled' => false]);

        $item = new TallcmsMenuItem;
        $item->setTranslation('label', config('app.locale', 'en'), 'Home');

        $page = new MenuItemsManager;
        $page->activeLocale = null;

        $this->assertSame('Home', (string) $page->getRecordLabel($item));
    }
}
