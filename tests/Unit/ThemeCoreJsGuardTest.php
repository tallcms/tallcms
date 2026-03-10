<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Guard test to ensure themes do not bundle TallCMS core JS.
 *
 * Core Alpine components (contact-form, comments, etc.) are loaded once
 * via @tallcmsCoreJs from the root Vite build. Theme app.js files must
 * NOT import resources/js/tallcms, as that creates per-theme drift and
 * requires rebuilding every theme when core logic changes.
 */
class ThemeCoreJsGuardTest extends TestCase
{
    public function test_no_theme_imports_core_tallcms_js(): void
    {
        $themesDir = base_path('themes');

        if (! is_dir($themesDir)) {
            $this->markTestSkipped('No themes directory (plugin mode).');
        }

        $themes = array_filter(glob("{$themesDir}/*"), 'is_dir');
        $violations = [];

        foreach ($themes as $themeDir) {
            $appJs = "{$themeDir}/resources/js/app.js";
            if (! file_exists($appJs)) {
                continue;
            }

            $contents = file_get_contents($appJs);
            if (preg_match('/^\s*import\s.*resources\/js\/tallcms/m', $contents)) {
                $violations[] = basename($themeDir);
            }
        }

        $this->assertEmpty(
            $violations,
            'These themes still import core TallCMS JS (should use @tallcmsCoreJs instead): '.implode(', ', $violations)
        );
    }

    public function test_theme_layouts_include_tallcms_core_js_directive(): void
    {
        $themesDir = base_path('themes');

        if (! is_dir($themesDir)) {
            $this->markTestSkipped('No themes directory (plugin mode).');
        }

        $themes = array_filter(glob("{$themesDir}/*"), 'is_dir');
        $missing = [];

        foreach ($themes as $themeDir) {
            $layout = "{$themeDir}/resources/views/layouts/app.blade.php";
            if (! file_exists($layout)) {
                continue;
            }

            $contents = file_get_contents($layout);
            if (! str_contains($contents, '@tallcmsCoreJs')) {
                $missing[] = basename($themeDir);
            }
        }

        $this->assertEmpty(
            $missing,
            'These theme layouts are missing @tallcmsCoreJs directive: '.implode(', ', $missing)
        );
    }
}
