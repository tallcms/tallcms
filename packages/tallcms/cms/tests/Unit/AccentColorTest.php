<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Filament\Blocks\Support\AccentColor;
use TallCms\Cms\Tests\TestCase;

class AccentColorTest extends TestCase
{
    public static function tokenVariantProvider(): array
    {
        return [
            // text
            ['text', 'primary', 'text-primary'],
            ['text', 'secondary', 'text-secondary'],
            ['text', 'accent', 'text-accent'],
            ['text', 'neutral', 'text-neutral'],
            ['text', 'info', 'text-info'],
            ['text', 'success', 'text-success'],
            ['text', 'warning', 'text-warning'],
            ['text', 'error', 'text-error'],

            // text20
            ['text20', 'primary', 'text-primary/20'],
            ['text20', 'secondary', 'text-secondary/20'],
            ['text20', 'accent', 'text-accent/20'],
            ['text20', 'neutral', 'text-neutral/20'],
            ['text20', 'info', 'text-info/20'],
            ['text20', 'success', 'text-success/20'],
            ['text20', 'warning', 'text-warning/20'],
            ['text20', 'error', 'text-error/20'],

            // tint5
            ['tint5', 'primary', 'bg-primary/5'],
            ['tint5', 'secondary', 'bg-secondary/5'],
            ['tint5', 'accent', 'bg-accent/5'],
            ['tint5', 'neutral', 'bg-neutral/5'],
            ['tint5', 'info', 'bg-info/5'],
            ['tint5', 'success', 'bg-success/5'],
            ['tint5', 'warning', 'bg-warning/5'],
            ['tint5', 'error', 'bg-error/5'],

            // tint10
            ['tint10', 'primary', 'bg-primary/10'],
            ['tint10', 'secondary', 'bg-secondary/10'],
            ['tint10', 'accent', 'bg-accent/10'],
            ['tint10', 'neutral', 'bg-neutral/10'],
            ['tint10', 'info', 'bg-info/10'],
            ['tint10', 'success', 'bg-success/10'],
            ['tint10', 'warning', 'bg-warning/10'],
            ['tint10', 'error', 'bg-error/10'],

            // fill
            ['fill', 'primary', 'bg-primary text-primary-content'],
            ['fill', 'secondary', 'bg-secondary text-secondary-content'],
            ['fill', 'accent', 'bg-accent text-accent-content'],
            ['fill', 'neutral', 'bg-neutral text-neutral-content'],
            ['fill', 'info', 'bg-info text-info-content'],
            ['fill', 'success', 'bg-success text-success-content'],
            ['fill', 'warning', 'bg-warning text-warning-content'],
            ['fill', 'error', 'bg-error text-error-content'],

            // bg
            ['bg', 'primary', 'bg-primary'],
            ['bg', 'secondary', 'bg-secondary'],
            ['bg', 'accent', 'bg-accent'],
            ['bg', 'neutral', 'bg-neutral'],
            ['bg', 'info', 'bg-info'],
            ['bg', 'success', 'bg-success'],
            ['bg', 'warning', 'bg-warning'],
            ['bg', 'error', 'bg-error'],

            // border
            ['border', 'primary', 'border-primary'],
            ['border', 'secondary', 'border-secondary'],
            ['border', 'accent', 'border-accent'],
            ['border', 'neutral', 'border-neutral'],
            ['border', 'info', 'border-info'],
            ['border', 'success', 'border-success'],
            ['border', 'warning', 'border-warning'],
            ['border', 'error', 'border-error'],

            // shadow10
            ['shadow10', 'primary', 'shadow-primary/10'],
            ['shadow10', 'secondary', 'shadow-secondary/10'],
            ['shadow10', 'accent', 'shadow-accent/10'],
            ['shadow10', 'neutral', 'shadow-neutral/10'],
            ['shadow10', 'info', 'shadow-info/10'],
            ['shadow10', 'success', 'shadow-success/10'],
            ['shadow10', 'warning', 'shadow-warning/10'],
            ['shadow10', 'error', 'shadow-error/10'],

            // badge (daisyUI badge-* modifier)
            ['badge', 'primary', 'badge-primary'],
            ['badge', 'secondary', 'badge-secondary'],
            ['badge', 'accent', 'badge-accent'],
            ['badge', 'neutral', 'badge-neutral'],
            ['badge', 'info', 'badge-info'],
            ['badge', 'success', 'badge-success'],
            ['badge', 'warning', 'badge-warning'],
            ['badge', 'error', 'badge-error'],
        ];
    }

    /**
     * @dataProvider tokenVariantProvider
     */
    public function test_each_helper_returns_expected_class(string $variant, string $token, string $expected): void
    {
        $method = $variant;
        $this->assertSame($expected, AccentColor::{$method}($token));
    }

    /**
     * @dataProvider tokenVariantProvider
     */
    public function test_resolve_dispatches_to_correct_helper(string $variant, string $token, string $expected): void
    {
        $this->assertSame($expected, AccentColor::resolve($variant, $token));
    }

    public function test_unknown_token_falls_back_to_primary_variant(): void
    {
        $this->assertSame('text-primary', AccentColor::text('not-a-real-token'));
        $this->assertSame('bg-primary/10', AccentColor::tint10('typo'));
        $this->assertSame('bg-primary text-primary-content', AccentColor::fill(''));
        $this->assertSame('shadow-primary/10', AccentColor::shadow10('garbage'));
        $this->assertSame('badge-primary', AccentColor::badge('made-up'));
    }

    public function test_unknown_variant_in_resolve_falls_back_to_text(): void
    {
        $this->assertSame('text-secondary', AccentColor::resolve('not-a-variant', 'secondary'));
        $this->assertSame('text-primary', AccentColor::resolve('typo'));
    }

    public function test_default_token_is_primary(): void
    {
        $this->assertSame('text-primary', AccentColor::text());
        $this->assertSame('bg-primary/5', AccentColor::tint5());
        $this->assertSame('border-primary', AccentColor::border());
    }
}
