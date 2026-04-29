<?php

declare(strict_types=1);

namespace Tests\Feature\Blocks;

use Tallcms\Pro\Blocks\TabsBlock;
use Tests\TestCase;

/**
 * Render tests for the Pro plugin's Tabs block.
 *
 * Lives in tallcms standalone (not the Pro plugin repo) because Pro
 * has no phpunit infra of its own and rendering needs heroicons,
 * which only bootstrap properly in the parent app.
 *
 * The TabsBlock active-indicator uses mode 2 (precomputed
 * $accentBorder + $accentFill inside @php match() arms) — see
 * tallcms-pro-plugin#1.
 */
class ProTabsBlockRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(TabsBlock::class)) {
            $this->markTestSkipped('Pro plugin not installed.');
        }
    }

    private function sampleConfig(array $overrides = []): array
    {
        return array_merge([
            'heading' => 'Tabs',
            'tabs' => [
                ['title' => 'Tab 1', 'content' => 'First tab'],
                ['title' => 'Tab 2', 'content' => 'Second tab'],
            ],
            'active_indicator' => 'underline',
        ], $overrides);
    }

    public function test_default_underline_uses_primary_border(): void
    {
        $html = TabsBlock::toHtml($this->sampleConfig(), []);
        $this->assertStringContainsString('border-primary', $html);
    }

    public function test_secondary_accent_recolors_underline(): void
    {
        $html = TabsBlock::toHtml($this->sampleConfig(['accent_color' => 'secondary']), []);
        $this->assertStringContainsString('border-secondary', $html);
        $this->assertStringNotContainsString('border-primary', $html);
    }

    public function test_filled_indicator_uses_fill_variant(): void
    {
        $config = $this->sampleConfig([
            'active_indicator' => 'filled',
            'accent_color' => 'success',
        ]);
        $html = TabsBlock::toHtml($config, []);
        $this->assertStringContainsString('bg-success', $html);
        $this->assertStringContainsString('text-success-content', $html);
    }

    public function test_default_indicator_emits_no_accent_class(): void
    {
        // 'default' indicator falls through to '' regardless of accent_color
        $config = $this->sampleConfig([
            'active_indicator' => 'default',
            'accent_color' => 'error',
        ]);
        $html = TabsBlock::toHtml($config, []);
        $this->assertStringNotContainsString('border-error', $html);
        $this->assertStringNotContainsString('bg-error text-error-content', $html);
    }

    public function test_unknown_accent_falls_back_to_primary(): void
    {
        $html = TabsBlock::toHtml($this->sampleConfig(['accent_color' => 'made-up']), []);
        $this->assertStringContainsString('border-primary', $html);
    }
}
