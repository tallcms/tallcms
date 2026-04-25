<?php

namespace TallCms\Cms\Tests\Feature;

use TallCms\Cms\Tests\TestCase;

/**
 * Contract test for the package's pre-built `resources/dist/tallcms.js` —
 * the runtime that plugin-mode @tallcmsCoreJs falls back to.
 *
 * The bundled JS has historically drifted from the source under
 * `resources/js/tallcms/components/`. Notable past incidents that this
 * test would have caught:
 *
 *   - contactForm rendered without an `errors` state, but the field
 *     templates read `errors.*` → silent Alpine expression errors
 *   - contactForm POSTed `_page_url` (snake_case), but the controller
 *     expected `_pageUrl` (camelCase) → "Invalid form configuration"
 *   - commentForm wasn't registered at all → comments under plugin
 *     mode were broken
 *
 * Until the package gets its own JS build pipeline, this test pins the
 * minimum contract the bundle MUST satisfy. If the source evolves and
 * the bundle isn't rebuilt + recopied, this test fires.
 */
class CoreJsRuntimeContractTest extends TestCase
{
    protected string $bundle;

    protected function setUp(): void
    {
        parent::setUp();

        $bundlePath = dirname(__DIR__, 2).'/resources/dist/tallcms.js';
        $this->assertFileExists(
            $bundlePath,
            'Package must ship resources/dist/tallcms.js — plugin-mode '
            .'@tallcmsCoreJs fallback resolves to it via vendor:publish.',
        );

        $this->bundle = file_get_contents($bundlePath);
    }

    public function test_registers_contact_form_alpine_component(): void
    {
        $this->assertStringContainsString(
            'Alpine.data("contactForm"',
            $this->bundle,
            'contactForm component must be registered. Without it, every '
            .'frontend page with a contact-form block logs '
            .'"Alpine Expression Error: contactForm is not defined".',
        );
    }

    public function test_registers_comment_form_alpine_component(): void
    {
        $this->assertStringContainsString(
            'Alpine.data("commentForm"',
            $this->bundle,
            'commentForm component must be registered. The comments block '
            .'binds `x-data="commentForm"`; without registration it stays '
            .'broken in plugin mode.',
        );
    }

    public function test_contact_form_initialises_errors_state(): void
    {
        // The field template (cms/blocks/dynamic-field.blade.php) reads
        // errors.<field>[0] and errors.hasError(<field>) — those resolve
        // through the Alpine state, which has to define `errors:` upfront
        // or accessing them throws "errors is not defined".
        $this->assertMatchesRegularExpression(
            '/errors\s*:\s*\{/',
            $this->bundle,
            'contactForm must initialise an `errors` state. Field templates '
            .'read errors.<field> and would throw without it.',
        );
    }

    public function test_contact_form_submits_pageUrl_camelCase(): void
    {
        // ContactFormController validates `_pageUrl`. A snake_case
        // `_page_url` payload trips signature normalization and the
        // submission rejects with "Invalid form configuration."
        $this->assertStringContainsString(
            '_pageUrl',
            $this->bundle,
            'Contact form submission payload must include `_pageUrl` '
            .'(camelCase) — the controller validates that exact key.',
        );
        $this->assertStringNotContainsString(
            '_page_url',
            $this->bundle,
            'Submission payload must not use the legacy snake_case '
            .'`_page_url` — controller expects `_pageUrl`.',
        );
    }

    public function test_bundle_is_not_an_empty_stub(): void
    {
        // Sanity: a 0-byte or single-line stub would slip past the other
        // assertions if Vite output disappeared. Min size keeps the
        // bundle from regressing into a placeholder.
        $this->assertGreaterThan(
            2000,
            strlen($this->bundle),
            'Bundle is suspiciously small — has it been replaced by a '
            .'placeholder? Expected a minified runtime > 2 KB.',
        );
    }
}
