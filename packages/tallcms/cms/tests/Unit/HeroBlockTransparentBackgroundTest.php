<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use TallCms\Cms\Filament\Blocks\ContentBlockBlock;
use TallCms\Cms\Filament\Blocks\HeroBlock;
use TallCms\Cms\Tests\TestCase;

class HeroBlockTransparentBackgroundTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_shared_background_options_list_transparent_last(): void
    {
        $method = new ReflectionMethod(ContentBlockBlock::class, 'getBackgroundOptions');
        $options = $method->invoke(null);

        $this->assertArrayHasKey('bg-transparent', $options);
        $this->assertSame('bg-transparent', array_key_last($options));
        $this->assertSame(__('tallcms::blocks.options.transparent'), $options['bg-transparent']);
    }

    public function test_hero_background_options_list_transparent_last(): void
    {
        $method = new ReflectionMethod(HeroBlock::class, 'getHeroBackgroundOptions');
        $options = $method->invoke(null);

        $this->assertArrayHasKey('bg-transparent', $options);
        $this->assertSame('bg-transparent', array_key_last($options));
        $this->assertSame('Transparent', $options['bg-transparent']);
    }

    public function test_hero_to_html_emits_transparent_fill_without_dark_text(): void
    {
        $html = HeroBlock::toHtml([
            'heading' => 'Transparent Hero',
            'subheading' => '',
            'background_color' => 'bg-transparent',
            'background_image' => null,
            'overlay_opacity' => 0,
            'layout' => 'centered',
            'height' => 'min-h-[70vh]',
            'text_alignment' => 'text-center',
            'parallax_effect' => false,
            'animation_type' => '',
            'animation_duration' => 'anim-duration-700',
        ], []);

        $this->assertStringContainsString('bg-transparent', $html);
        $this->assertStringContainsString('text-base-content', $html);
        $this->assertStringNotContainsString('text-neutral-content', $html);
        $this->assertStringNotContainsString('bg-gradient-to-br from-primary to-secondary', $html);
    }

    public function test_content_block_to_html_emits_transparent_background(): void
    {
        $html = ContentBlockBlock::toHtml([
            'title' => 'Band',
            'body' => '<p>Hello</p>',
            'background' => 'bg-transparent',
            'padding' => 'py-16',
            'animation_type' => '',
            'animation_duration' => 'anim-duration-700',
        ], []);

        $this->assertStringContainsString('bg-transparent', $html);
    }
}
