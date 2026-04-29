<?php

declare(strict_types=1);

namespace Tests\Feature\Blocks;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Render test for the Elevate theme's Features view, which uses the
 * precomputed-variable consumption mode (FQCN call to
 * AccentColor::tint5() inside an @php block, not the @accent directive).
 *
 * Asserts the emphasized first-card branch picks up the accent_color
 * via the precomputed $accentTint5 variable, and that the multi-color
 * gradient backdrop is intentionally left as primary→secondary.
 */
class FeaturesBlockElevateRenderTest extends TestCase
{
    private string $elevateView = __DIR__.'/../../../packages/tallcms/cms/resources/themes/elevate/resources/views/cms/blocks/features.blade.php';

    /**
     * Build the view data the way FeaturesBlock::renderBlock() does, so
     * the Elevate view receives the same shape it would in production.
     * Keeps `count($features) >= 6` and `columns => '3'` (Elevate's
     * emphasis triggers).
     */
    private function viewData(array $overrides = []): array
    {
        $features = array_fill(0, 6, [
            'icon_type' => 'heroicon',
            'icon' => 'heroicon-o-bolt',
            'title' => 'Feature',
            'description' => 'Description.',
        ]);

        return array_merge([
            'id' => 'features',
            'heading' => 'Test',
            'subheading' => '',
            'features' => $features,
            'columns' => '3',
            'card_style' => 'card shadow-xl bg-base-100',
            'icon_position' => 'top',
            'text_alignment' => 'text-center',
            'icon_size' => 'w-10 h-10',
            'accent_color' => 'primary',
            'contentWidthClass' => 'max-w-7xl mx-auto',
            'contentPadding' => 'px-4 sm:px-6 lg:px-8',
            'padding' => 'py-16',
            'first_section' => false,
            'anchor_id' => null,
            'css_classes' => '',
            'animation_type' => '',
            'animation_duration' => 'anim-duration-500',
            'animation_stagger' => false,
            'animation_stagger_delay' => 100,
        ], $overrides);
    }

    public function test_default_emphasis_card_uses_primary_tint5(): void
    {
        $this->assertFileExists($this->elevateView);

        $html = View::file($this->elevateView, $this->viewData())->render();

        $this->assertStringContainsString('bg-primary/5', $html);
    }

    public function test_secondary_accent_emphasis_card_uses_secondary_tint5(): void
    {
        $html = View::file($this->elevateView, $this->viewData(['accent_color' => 'secondary']))->render();

        $this->assertStringContainsString('bg-secondary/5', $html, 'Emphasized card should use secondary tint when accent_color=secondary');
        $this->assertStringNotContainsString('bg-primary/5', $html, 'Primary tint should not appear on emphasized card');
    }

    public function test_accent_color_recolors_icon_text(): void
    {
        $html = View::file($this->elevateView, $this->viewData(['accent_color' => 'info']))->render();

        // Icon inside the gradient container picks up @accent('text', $accent)
        $this->assertStringContainsString('text-info', $html);
    }

    public function test_gradient_icon_container_is_intentionally_unchanged(): void
    {
        // The bg-gradient-to-br from-primary/10 to-secondary/10 is theme
        // decoration and intentionally does NOT track accent_color.
        $html = View::file($this->elevateView, $this->viewData(['accent_color' => 'error']))->render();

        $this->assertStringContainsString('from-primary/10 to-secondary/10', $html);
    }
}
