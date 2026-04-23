<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Contract tests for the @tallcmsDaisyUIBoot Blade directive.
 *
 * Themes used to copy-paste the daisyUI boot script into their own layouts.
 * That meant a bug in the script (the localStorage key collision with
 * Filament's admin dark-mode toggle) couldn't be fixed by a tallcms:update —
 * themes/ is preserved across updates. Shipping the script as a core-owned
 * directive closes that gap: themes call @tallcmsDaisyUIBoot, core ships
 * the actual JS, and bug fixes ship with every CMS release.
 *
 * These tests lock the directive's output contract so we don't regress
 * to the pre-fix (unnamespaced) state or accidentally drop the key logic.
 */
class DaisyUIBootDirectiveTest extends TestCase
{
    public function test_directive_outputs_namespaced_localstorage_keys(): void
    {
        $html = $this->renderDirective();

        // Must use the namespaced keys — the whole reason this directive exists.
        $this->assertStringContainsString("'tallcms-theme'", $html);
        $this->assertStringContainsString("'tallcms-theme-default'", $html);

        // Must NOT use the bare keys that collide with Filament's admin
        // dark-mode localStorage.
        $this->assertStringNotContainsString("getItem('theme')", $html);
        $this->assertStringNotContainsString("setItem('theme',", $html);
        $this->assertStringNotContainsString("getItem('theme-default')", $html);
    }

    public function test_directive_reads_server_rendered_default_theme_attribute(): void
    {
        $html = $this->renderDirective();

        $this->assertStringContainsString("getAttribute('data-default-theme')", $html);
    }

    public function test_directive_clears_override_when_server_default_changes(): void
    {
        $html = $this->renderDirective();

        // Reset logic: when storedDefault differs from serverDefault, clear
        // the visitor's override so admin preset changes propagate.
        $this->assertStringContainsString('removeItem', $html);
        $this->assertStringContainsString('setItem', $html);
    }

    public function test_directive_applies_saved_theme_to_data_theme_attribute(): void
    {
        $html = $this->renderDirective();

        $this->assertStringContainsString("setAttribute('data-theme'", $html);
    }

    protected function renderDirective(): string
    {
        return view()->file(__DIR__.'/../fixtures/daisyui-boot-directive-probe.blade.php')->render();
    }
}
