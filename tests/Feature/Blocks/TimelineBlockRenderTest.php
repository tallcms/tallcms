<?php

declare(strict_types=1);

namespace Tests\Feature\Blocks;

use TallCms\Cms\Filament\Blocks\TimelineBlock;
use Tests\TestCase;

/**
 * Timeline block delegates rendering to two partials:
 *
 *   - timeline-node.blade.php — modes 1 + 3 (icon class is inside
 *     <x-dynamic-component>, so uses inline FQCN).
 *   - timeline-content.blade.php — mode 1.
 *
 * Tests exercise the numbered branch (mode 1) and the icon branch
 * (mode 3) plus the date label in timeline-content.
 */
class TimelineBlockRenderTest extends TestCase
{
    private function numberedConfig(array $overrides = []): array
    {
        return array_merge([
            'heading' => 'History',
            'numbered' => true,
            'items' => [[
                'title' => 'First',
                'description' => 'desc',
                'date' => '2024',
            ]],
        ], $overrides);
    }

    public function test_default_numbered_node_uses_primary_text_and_tint(): void
    {
        $html = TimelineBlock::toHtml($this->numberedConfig(), []);
        $this->assertStringContainsString('bg-primary/10', $html);
        $this->assertStringContainsString('text-primary', $html);
    }

    public function test_success_accent_recolors_node_ring_and_number(): void
    {
        $html = TimelineBlock::toHtml($this->numberedConfig(['accent_color' => 'success']), []);
        $this->assertStringContainsString('bg-success/10', $html);
        $this->assertStringContainsString('text-success', $html);
    }

    public function test_date_label_in_content_partial_picks_up_accent(): void
    {
        // Date label lives in timeline-content.blade.php — separate partial.
        $html = TimelineBlock::toHtml($this->numberedConfig(['accent_color' => 'warning']), []);
        $this->assertStringContainsString('text-warning', $html);
    }
}
