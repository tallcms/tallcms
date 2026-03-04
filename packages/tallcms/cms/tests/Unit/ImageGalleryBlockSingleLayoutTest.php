<?php

namespace TallCms\Cms\Tests\Unit;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Illuminate\Support\Facades\Storage;
use TallCms\Cms\Filament\Blocks\ImageGalleryBlock;
use TallCms\Cms\Tests\TestCase;

class ImageGalleryBlockSingleLayoutTest extends TestCase
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

        // Fake the public disk used by cms_media_disk()
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

        // Decode unicode escapes (\u0022 → ")
        $jsonString = json_decode('"'.$matches[1].'"');

        $items = json_decode($jsonString, true);
        $this->assertIsArray($items, 'Lightbox items should decode to an array');

        return $items;
    }

    protected function renderSingleLayout(array $overrides = []): string
    {
        $config = array_merge([
            'source' => 'manual',
            'layout' => 'single',
            'images' => [$this->createFakeImage('cms/galleries/test-image.jpg')],
            'title' => '',
            'caption' => '',
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

    public function test_single_layout_contains_single_container_class(): void
    {
        $html = $this->renderSingleLayout();

        $this->assertStringContainsString('image-gallery-single', $html);
    }

    public function test_single_layout_does_not_contain_grid_classes(): void
    {
        $html = $this->renderSingleLayout();

        $this->assertStringNotContainsString('grid-cols-', $html);
        $this->assertStringNotContainsString('columns-1', $html);
        $this->assertStringNotContainsString('snap-x', $html);
    }

    public function test_single_layout_lightbox_has_exactly_one_item(): void
    {
        $html = $this->renderSingleLayout();

        $items = $this->extractLightboxItems($html);
        $this->assertCount(1, $items, 'Single layout lightbox should contain exactly 1 item');
    }

    public function test_single_layout_includes_caption(): void
    {
        $html = $this->renderSingleLayout([
            'caption' => 'A beautiful sunset photo',
        ]);

        $this->assertStringContainsString('A beautiful sunset photo', $html);
    }

    public function test_single_layout_renders_only_first_image_when_multiple_provided(): void
    {
        $images = [
            $this->createFakeImage('cms/galleries/image1.jpg'),
            $this->createFakeImage('cms/galleries/image2.jpg'),
            $this->createFakeImage('cms/galleries/image3.jpg'),
            $this->createFakeImage('cms/galleries/image4.jpg'),
        ];

        $html = $this->renderSingleLayout(['images' => $images]);

        // Should contain the first image
        $this->assertStringContainsString(Storage::disk('public')->url('cms/galleries/image1.jpg'), $html);

        // Lightbox should still have only 1 item (hard clamp)
        $items = $this->extractLightboxItems($html);
        $this->assertCount(1, $items, 'Hard clamp should limit to 1 item even with multiple images');
    }

    public function test_single_layout_hides_lightbox_nav_buttons(): void
    {
        $html = $this->renderSingleLayout();

        // Prev/next buttons should have x-show="items.length > 1"
        $this->assertStringContainsString('x-show="items.length > 1"', $html);
    }

    public function test_grid_layout_still_works_normally(): void
    {
        $images = [
            $this->createFakeImage('cms/galleries/img1.jpg'),
            $this->createFakeImage('cms/galleries/img2.jpg'),
            $this->createFakeImage('cms/galleries/img3.jpg'),
        ];

        $config = [
            'source' => 'manual',
            'layout' => 'grid-3',
            'images' => $images,
            'title' => '',
            'caption' => '',
            'image_size' => 'medium',
            'background' => 'bg-base-100',
            'padding' => 'py-16',
            'first_section' => false,
            'animation_type' => '',
            'animation_duration' => 'anim-duration-700',
            'animation_stagger' => false,
            'animation_stagger_delay' => '100',
        ];

        $html = ImageGalleryBlock::toHtml($config, []);

        $this->assertStringContainsString('grid-cols-', $html);
        $this->assertStringNotContainsString('image-gallery-single', $html);

        // Lightbox should have all 3 items
        $items = $this->extractLightboxItems($html);
        $this->assertCount(3, $items);
    }
}
