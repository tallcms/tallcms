<?php

declare(strict_types=1);

namespace Tests\Feature\Blocks;

use TallCms\Cms\Filament\Blocks\StatsBlock;
use Tests\TestCase;

/**
 * Stats block uses mode 1 (plain HTML <div>) on .stat-figure and .stat-value.
 * Migration replaced text-primary with @accent('text', $accent_color ?? 'primary').
 */
class StatsBlockRenderTest extends TestCase
{
    private function sampleConfig(array $overrides = []): array
    {
        return array_merge([
            'heading' => 'Test',
            'stats' => [[
                'icon' => 'heroicon-o-bolt',
                'value' => '99',
                'label' => 'Speed',
            ]],
        ], $overrides);
    }

    public function test_default_uses_primary_text(): void
    {
        $html = StatsBlock::toHtml($this->sampleConfig(), []);
        $this->assertStringContainsString('text-primary', $html);
    }

    public function test_secondary_recolors_stat_figure_and_value(): void
    {
        $html = StatsBlock::toHtml($this->sampleConfig(['accent_color' => 'secondary']), []);
        $this->assertStringContainsString('stat-figure text-secondary', $html);
        $this->assertStringContainsString('stat-value text-secondary', $html);
    }

    public function test_unknown_accent_falls_back_to_primary(): void
    {
        $html = StatsBlock::toHtml($this->sampleConfig(['accent_color' => 'made-up']), []);
        $this->assertStringContainsString('stat-figure text-primary', $html);
    }
}
