<?php

declare(strict_types=1);

namespace Tests\Feature\Blocks;

use TallCms\Cms\Filament\Blocks\HowToBlock;
use Tests\TestCase;

/**
 * How-To block uses mode 1 (plain HTML <div>) on the step number circle.
 * Migration replaced 'bg-primary text-primary-content' with
 * @accent('fill', $accent_color ?? 'primary').
 */
class HowToBlockRenderTest extends TestCase
{
    private function sampleConfig(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Get Started',
            'steps' => [[
                'title' => 'Step 1',
                'description' => 'Do this thing.',
            ]],
        ], $overrides);
    }

    public function test_default_step_circle_uses_primary_fill(): void
    {
        $html = HowToBlock::toHtml($this->sampleConfig(), []);
        $this->assertStringContainsString('bg-primary text-primary-content', $html);
    }

    public function test_accent_recolors_step_circle(): void
    {
        $html = HowToBlock::toHtml($this->sampleConfig(['accent_color' => 'accent']), []);
        $this->assertStringContainsString('bg-accent text-accent-content', $html);
        $this->assertStringNotContainsString('bg-primary text-primary-content', $html);
    }

    public function test_warning_token_renders_warning_fill(): void
    {
        $html = HowToBlock::toHtml($this->sampleConfig(['accent_color' => 'warning']), []);
        $this->assertStringContainsString('bg-warning text-warning-content', $html);
    }
}
