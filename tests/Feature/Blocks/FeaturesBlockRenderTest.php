<?php

declare(strict_types=1);

namespace Tests\Feature\Blocks;

use TallCms\Cms\Filament\Blocks\FeaturesBlock;
use Tests\TestCase;

/**
 * End-to-end render tests for the canonical Features block view —
 * verifies the accent_color form field flows through renderBlock()
 * into the Blade view and the @accent directive emits the right
 * Tailwind classes.
 *
 * Asserts SPECIFIC migrated elements change, not global absence of
 * primary classes (links/buttons in the same view legitimately keep
 * primary even when the icon accent is something else).
 *
 * Lives in the standalone test suite because the package Testbench
 * doesn't ship blade-ui-kit/blade-heroicons; the Features view uses
 * <x-heroicon-*> components.
 */
class FeaturesBlockRenderTest extends TestCase
{
    private function sampleConfig(array $overrides = []): array
    {
        return array_merge([
            'heading' => 'Test Features',
            'features' => [
                [
                    'icon_type' => 'heroicon',
                    'icon' => 'heroicon-o-bolt',
                    'title' => 'Fast',
                    'description' => 'Lightning fast.',
                ],
            ],
        ], $overrides);
    }

    public function test_default_render_uses_primary_accent(): void
    {
        $html = FeaturesBlock::toHtml($this->sampleConfig(), []);

        $this->assertStringContainsString('bg-primary/10', $html, 'Icon container should default to primary tint');
        $this->assertStringContainsString('text-primary', $html, 'Icon should default to primary text');
    }

    public function test_secondary_accent_recolors_icon_container_and_icon(): void
    {
        $html = FeaturesBlock::toHtml($this->sampleConfig(['accent_color' => 'secondary']), []);

        $this->assertStringContainsString('bg-secondary/10', $html);
        $this->assertStringContainsString('text-secondary', $html);
        $this->assertStringNotContainsString('bg-primary/10', $html);
    }

    public function test_accent_token_recolors_icon_container_and_icon(): void
    {
        $html = FeaturesBlock::toHtml($this->sampleConfig(['accent_color' => 'accent']), []);

        $this->assertStringContainsString('bg-accent/10', $html);
        $this->assertStringContainsString('text-accent', $html);
    }

    public function test_error_accent_works_for_status_color(): void
    {
        $html = FeaturesBlock::toHtml($this->sampleConfig(['accent_color' => 'error']), []);

        $this->assertStringContainsString('bg-error/10', $html);
        $this->assertStringContainsString('text-error', $html);
    }

    public function test_unknown_accent_falls_back_to_primary(): void
    {
        $html = FeaturesBlock::toHtml($this->sampleConfig(['accent_color' => 'made-up']), []);

        $this->assertStringContainsString('bg-primary/10', $html);
        $this->assertStringContainsString('text-primary', $html);
    }

    public function test_fallback_star_icon_also_picks_up_accent(): void
    {
        // No icon → falls through to <x-heroicon-o-star>
        $config = $this->sampleConfig([
            'accent_color' => 'info',
            'features' => [[
                'icon_type' => 'heroicon',
                'icon' => '',
                'title' => 'No Icon',
            ]],
        ]);

        $html = FeaturesBlock::toHtml($config, []);

        $this->assertStringContainsString('text-info', $html, 'Fallback star icon should also use accent color');
    }
}
