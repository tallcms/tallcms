<?php

declare(strict_types=1);

namespace Tests\Feature\Blocks;

use TallCms\Cms\Filament\Blocks\PricingBlock;
use Tests\TestCase;

/**
 * Pricing block uses mode 2 — the popular-plan card class is built
 * inside @php match() arms with precomputed $accentTint5 + $accentBorder.
 * The @accent directive doesn't work in that scope.
 */
class PricingBlockRenderTest extends TestCase
{
    private function popularPlanConfig(array $overrides = []): array
    {
        return array_merge([
            'section_title' => 'Pricing',
            'plans' => [
                [
                    'plan_name' => 'Starter',
                    'price' => '0',
                    'is_popular' => false,
                    'features' => [['text' => 'Basic', 'is_included' => true]],
                ],
                [
                    'plan_name' => 'Pro',
                    'price' => '29',
                    'is_popular' => true,
                    'features' => [['text' => 'Everything', 'is_included' => true]],
                ],
            ],
        ], $overrides);
    }

    public function test_default_popular_card_uses_primary_tint_and_border(): void
    {
        $html = PricingBlock::toHtml($this->popularPlanConfig(), []);
        $this->assertStringContainsString('bg-primary/5', $html);
        $this->assertStringContainsString('border-primary', $html);
    }

    public function test_error_accent_recolors_popular_card(): void
    {
        $html = PricingBlock::toHtml($this->popularPlanConfig(['accent_color' => 'error']), []);
        $this->assertStringContainsString('bg-error/5', $html);
        $this->assertStringContainsString('border-error', $html);
        $this->assertStringNotContainsString('bg-primary/5', $html);
    }

    public function test_non_popular_plan_unaffected_by_accent(): void
    {
        // The "Starter" plan is not popular — its card uses bg-base-100/200.
        // Changing accent_color must not bleed into base-card classes.
        $html = PricingBlock::toHtml($this->popularPlanConfig(['accent_color' => 'success']), []);
        $this->assertStringContainsString('bg-success/5', $html, 'Popular card recolors');
        $this->assertStringContainsString('bg-base-200', $html, 'Non-popular cards keep base-200');
    }
}
