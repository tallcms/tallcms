<?php

namespace TallCms\Cms\Tests\Unit;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Illuminate\Support\Facades\Storage;
use TallCms\Cms\Filament\Blocks\ImageGalleryBlock;
use TallCms\Cms\Tests\TestCase;

class ImageGalleryBlockGridLayoutTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    protected function createFakeImage(string $path): string
    {
        Storage::disk('public')->put($path, 'fake-image-content');

        return $path;
    }

    /**
     * Extract the lightbox items array from the rendered HTML.
     * Js::from() outputs JSON.parse('...') with \u0022 unicode escapes.
     */
    protected function extractLightboxItems(string $html): array
    {
        preg_match("/items:\s*JSON\.parse\('(.+?)'\)/", $html, $matches);
        $this->assertNotEmpty($matches, 'Lightbox items data should be present');

        $jsonString = json_decode('"'.$matches[1].'"');

        $items = json_decode($jsonString, true);
        $this->assertIsArray($items, 'Lightbox items should decode to an array');

        return $items;
    }

    protected function renderLayout(string $layout, array $overrides = []): string
    {
        $config = array_merge([
            'source' => 'manual',
            'layout' => $layout,
            'images' => [$this->createFakeImage('cms/galleries/test-image.jpg')],
            'title' => '',
            'image_size' => 'medium',
            'background' => 'bg-base-100',
            'padding' => 'py-16',
            'first_section' => false,
            'animation_type' => '',
            'animation_duration' => 'anim-duration-700',
            'animation_stagger' => false,
            'animation_stagger_delay' => '100',
        ], $overrides);

        return ImageGalleryBlock::toHtml($config, []);
    }

    public function test_grid1_renders_with_grid_cols_1_class(): void
    {
        $html = $this->renderLayout('grid-1');

        $this->assertStringContainsString('grid-cols-1', $html);
        $this->assertStringContainsString('grid', $html);
    }

    public function test_grid1_does_not_contain_multi_column_classes(): void
    {
        $html = $this->renderLayout('grid-1');

        $this->assertStringNotContainsString('md:grid-cols-2', $html);
        $this->assertStringNotContainsString('lg:grid-cols-3', $html);
        $this->assertStringNotContainsString('lg:grid-cols-4', $html);
    }

    public function test_grid1_renders_all_images(): void
    {
        $images = [
            $this->createFakeImage('cms/galleries/image1.jpg'),
            $this->createFakeImage('cms/galleries/image2.jpg'),
            $this->createFakeImage('cms/galleries/image3.jpg'),
        ];

        $html = $this->renderLayout('grid-1', ['images' => $images]);

        $items = $this->extractLightboxItems($html);
        $this->assertCount(3, $items, 'Grid-1 should render all images, not clamp to 1');
    }

    public function test_grid1_uses_same_renderer_as_other_grids(): void
    {
        $html = $this->renderLayout('grid-1');

        // Should use the grid renderer (contains "grid" class wrapper), not masonry or carousel
        $this->assertStringNotContainsString('columns-1 md:columns-2', $html);
        $this->assertStringNotContainsString('snap-x', $html);
    }

    public function test_grid3_layout_unchanged(): void
    {
        $images = [
            $this->createFakeImage('cms/galleries/img1.jpg'),
            $this->createFakeImage('cms/galleries/img2.jpg'),
            $this->createFakeImage('cms/galleries/img3.jpg'),
        ];

        $html = $this->renderLayout('grid-3', ['images' => $images]);

        $this->assertStringContainsString('lg:grid-cols-3', $html);

        $items = $this->extractLightboxItems($html);
        $this->assertCount(3, $items);
    }

    public function test_lightbox_nav_hidden_for_single_item(): void
    {
        $html = $this->renderLayout('grid-1');

        $this->assertStringContainsString('x-show="items.length > 1"', $html);
    }
}
