<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use TallCms\Cms\Tests\TestCase;

/**
 * Verifies that the @accent Blade directive is registered by
 * TallCmsServiceProvider and dispatches to AccentColor::resolve().
 */
class AccentColorDirectiveTest extends TestCase
{
    public function test_directive_emits_text_class(): void
    {
        $this->assertSame(
            'text-secondary',
            Blade::render("@accent('text', 'secondary')")
        );
    }

    public function test_directive_emits_tint10_class(): void
    {
        $this->assertSame(
            'bg-info/10',
            Blade::render("@accent('tint10', 'info')")
        );
    }

    public function test_directive_emits_tint5_class(): void
    {
        $this->assertSame(
            'bg-success/5',
            Blade::render("@accent('tint5', 'success')")
        );
    }

    public function test_directive_emits_fill_class(): void
    {
        $this->assertSame(
            'bg-warning text-warning-content',
            Blade::render("@accent('fill', 'warning')")
        );
    }

    public function test_directive_emits_border_class(): void
    {
        $this->assertSame(
            'border-error',
            Blade::render("@accent('border', 'error')")
        );
    }

    public function test_directive_emits_shadow10_class(): void
    {
        $this->assertSame(
            'shadow-accent/10',
            Blade::render("@accent('shadow10', 'accent')")
        );
    }

    public function test_directive_emits_text20_class(): void
    {
        $this->assertSame(
            'text-neutral/20',
            Blade::render("@accent('text20', 'neutral')")
        );
    }

    public function test_directive_emits_bg_class(): void
    {
        $this->assertSame(
            'bg-primary',
            Blade::render("@accent('bg', 'primary')")
        );
    }

    public function test_directive_accepts_runtime_variables(): void
    {
        $this->assertSame(
            'text-accent',
            Blade::render(
                '@accent(\'text\', $accent)',
                ['accent' => 'accent']
            )
        );
    }

    public function test_directive_falls_back_to_primary_for_unknown_token(): void
    {
        $this->assertSame(
            'bg-primary/10',
            Blade::render("@accent('tint10', 'made-up-token')")
        );
    }
}
