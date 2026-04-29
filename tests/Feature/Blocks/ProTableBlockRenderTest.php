<?php

declare(strict_types=1);

namespace Tests\Feature\Blocks;

use Tallcms\Pro\Blocks\TableBlock;
use Tests\TestCase;

/**
 * Render tests for the Pro plugin's Table block.
 *
 * The row-highlight tint uses mode 2 (precomputed $accentTint10
 * inside @php) — that single variable serves both a plain <tr>
 * (mode 1) and a <tallcms::animation-wrapper> tag attribute
 * (mode 3) without separate computation. See tallcms-pro-plugin#1.
 */
class ProTableBlockRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(TableBlock::class)) {
            $this->markTestSkipped('Pro plugin not installed.');
        }
    }

    private function sampleConfig(array $overrides = []): array
    {
        return array_merge([
            'headers' => [
                ['label' => 'Feature', 'align' => 'left'],
                ['label' => 'Value', 'align' => 'right'],
            ],
            'rows' => [
                [
                    'cells' => [['value' => 'Speed'], ['value' => 'Fast']],
                    'highlight' => false,
                ],
                [
                    'cells' => [['value' => 'Best'], ['value' => 'Yes']],
                    'highlight' => true,
                ],
            ],
        ], $overrides);
    }

    public function test_default_highlighted_row_uses_primary_tint(): void
    {
        $html = TableBlock::toHtml($this->sampleConfig(), []);
        $this->assertStringContainsString('bg-primary/10', $html);
    }

    public function test_info_accent_recolors_highlighted_row(): void
    {
        $html = TableBlock::toHtml($this->sampleConfig(['accent_color' => 'info']), []);
        $this->assertStringContainsString('bg-info/10', $html);
        $this->assertStringNotContainsString('bg-primary/10', $html);
    }

    public function test_non_highlighted_rows_unaffected_by_accent(): void
    {
        $config = $this->sampleConfig([
            'accent_color' => 'warning',
            'rows' => [
                ['cells' => [['value' => 'A']], 'highlight' => false],
                ['cells' => [['value' => 'B']], 'highlight' => false],
            ],
        ]);
        $html = TableBlock::toHtml($config, []);
        $this->assertStringNotContainsString('bg-warning/10', $html);
    }

    public function test_unknown_accent_falls_back_to_primary(): void
    {
        $html = TableBlock::toHtml($this->sampleConfig(['accent_color' => 'made-up']), []);
        $this->assertStringContainsString('bg-primary/10', $html);
    }
}
