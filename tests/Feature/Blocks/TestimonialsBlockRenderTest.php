<?php

declare(strict_types=1);

namespace Tests\Feature\Blocks;

use TallCms\Cms\Filament\Blocks\TestimonialsBlock;
use Tests\TestCase;

/**
 * Testimonials block uses mode 1 (plain HTML <div>) for both the quote
 * marks (text20 variant) and the avatar wrap (tint10 + text variants).
 * The Elevate theme's gradient quote marks (text-gradient-primary) are
 * intentionally unchanged — that's a theme-decoration design call.
 */
class TestimonialsBlockRenderTest extends TestCase
{
    private function sampleConfig(array $overrides = []): array
    {
        return array_merge([
            'heading' => 'Reviews',
            // 'quote-marks' substring triggers $isQuoteMarks in the view.
            'card_style' => 'card bg-base-200 quote-marks',
            'testimonials' => [[
                'author_name' => 'Alice',
                'quote' => 'Great product.',
                'rating' => 5,
            ]],
        ], $overrides);
    }

    public function test_default_quote_mark_uses_primary20(): void
    {
        $html = TestimonialsBlock::toHtml($this->sampleConfig(), []);
        $this->assertStringContainsString('text-primary/20', $html);
    }

    public function test_default_avatar_wrap_uses_primary_tint10_and_text(): void
    {
        $html = TestimonialsBlock::toHtml($this->sampleConfig(), []);
        $this->assertStringContainsString('bg-primary/10', $html);
        $this->assertStringContainsString('text-primary', $html);
    }

    public function test_info_accent_recolors_quote_marks_and_avatar(): void
    {
        $html = TestimonialsBlock::toHtml($this->sampleConfig(['accent_color' => 'info']), []);
        $this->assertStringContainsString('text-info/20', $html);
        $this->assertStringContainsString('bg-info/10', $html);
        $this->assertStringContainsString('text-info', $html);
    }
}
