<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use TallCms\Cms\Models\TallcmsMedia;
use TallCms\Cms\Tests\TestCase;

class TallcmsMediaModelTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function makeMedia(array $overrides = []): TallcmsMedia
    {
        return TallcmsMedia::create(array_merge([
            'name' => 'test-file',
            'file_name' => 'test-file.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/test-file.jpg',
            'disk' => 'public',
            'size' => 1024,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Deletion — original file
    // -------------------------------------------------------------------------

    public function test_delete_removes_file_from_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/test-file.jpg', 'content');

        $media = $this->makeMedia();

        $media->delete();

        Storage::disk('public')->assertMissing('media/test-file.jpg');
    }

    public function test_delete_removes_file_from_custom_disk(): void
    {
        Storage::fake('cms-media');
        Storage::disk('cms-media')->put('media/test-file.jpg', 'content');

        $media = $this->makeMedia(['disk' => 'cms-media']);

        $media->delete();

        Storage::disk('cms-media')->assertMissing('media/test-file.jpg');
    }

    public function test_delete_does_not_error_when_file_already_missing(): void
    {
        Storage::fake('public');
        // Intentionally not putting the file on disk

        $media = $this->makeMedia();

        // Should not throw
        $media->delete();

        $this->assertDatabaseMissing('tallcms_media', ['id' => $media->id]);
    }

    public function test_delete_removes_record_from_database(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/test-file.jpg', 'content');

        $media = $this->makeMedia();
        $id = $media->id;

        $media->delete();

        $this->assertNull(TallcmsMedia::find($id));
    }

    // -------------------------------------------------------------------------
    // Deletion — variants
    // -------------------------------------------------------------------------

    public function test_delete_removes_variant_files_from_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/photo.jpg', 'original');
        Storage::disk('public')->put('media/photo-thumbnail.webp', 'thumb');
        Storage::disk('public')->put('media/photo-medium.webp', 'medium');
        Storage::disk('public')->put('media/photo-large.webp', 'large');

        $media = $this->makeMedia([
            'path' => 'media/photo.jpg',
            'has_variants' => true,
            'meta' => [
                'variants' => [
                    'thumbnail' => 'media/photo-thumbnail.webp',
                    'medium' => 'media/photo-medium.webp',
                    'large' => 'media/photo-large.webp',
                ],
            ],
        ]);

        $media->delete();

        Storage::disk('public')->assertMissing('media/photo.jpg');
        Storage::disk('public')->assertMissing('media/photo-thumbnail.webp');
        Storage::disk('public')->assertMissing('media/photo-medium.webp');
        Storage::disk('public')->assertMissing('media/photo-large.webp');
    }

    public function test_delete_removes_variants_from_custom_disk(): void
    {
        Storage::fake('cms-media');
        Storage::disk('cms-media')->put('media/photo.jpg', 'original');
        Storage::disk('cms-media')->put('media/photo-thumbnail.webp', 'thumb');

        $media = $this->makeMedia([
            'disk' => 'cms-media',
            'path' => 'media/photo.jpg',
            'has_variants' => true,
            'meta' => [
                'variants' => [
                    'thumbnail' => 'media/photo-thumbnail.webp',
                ],
            ],
        ]);

        $media->delete();

        Storage::disk('cms-media')->assertMissing('media/photo.jpg');
        Storage::disk('cms-media')->assertMissing('media/photo-thumbnail.webp');
    }

    public function test_delete_skips_variant_cleanup_when_has_variants_is_false(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/photo.jpg', 'content');

        $media = $this->makeMedia([
            'path' => 'media/photo.jpg',
            'has_variants' => false,
            'meta' => [],
        ]);

        // Should not throw even though there are no variants to clean up
        $media->delete();

        Storage::disk('public')->assertMissing('media/photo.jpg');
    }

    // -------------------------------------------------------------------------
    // URL generation
    // -------------------------------------------------------------------------

    public function test_url_attribute_uses_model_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/photo.jpg', 'content');

        $media = $this->makeMedia(['path' => 'media/photo.jpg', 'disk' => 'public']);

        $this->assertStringContainsString('media/photo.jpg', $media->url);
    }

    public function test_url_attribute_uses_custom_disk(): void
    {
        Storage::fake('cms-media');
        Storage::disk('cms-media')->put('media/photo.jpg', 'content');

        $media = $this->makeMedia(['path' => 'media/photo.jpg', 'disk' => 'cms-media']);

        $this->assertStringContainsString('media/photo.jpg', $media->url);
    }

    public function test_get_variant_url_returns_variant_path_from_model_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/photo.jpg', 'original');
        Storage::disk('public')->put('media/photo-medium.webp', 'medium');

        $media = $this->makeMedia([
            'path' => 'media/photo.jpg',
            'disk' => 'public',
            'has_variants' => true,
            'meta' => [
                'variants' => ['medium' => 'media/photo-medium.webp'],
            ],
        ]);

        $url = $media->getVariantUrl('medium');

        $this->assertStringContainsString('media/photo-medium.webp', $url);
    }

    public function test_get_variant_url_falls_back_to_original_when_variant_absent(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/photo.jpg', 'original');

        $media = $this->makeMedia(['path' => 'media/photo.jpg', 'meta' => []]);

        $url = $media->getVariantUrl('thumbnail');

        $this->assertStringContainsString('media/photo.jpg', $url);
    }

    public function test_get_variant_url_uses_custom_disk_for_variants(): void
    {
        Storage::fake('cms-media');
        Storage::disk('cms-media')->put('media/photo.jpg', 'original');
        Storage::disk('cms-media')->put('media/photo-thumbnail.webp', 'thumb');

        $media = $this->makeMedia([
            'disk' => 'cms-media',
            'path' => 'media/photo.jpg',
            'has_variants' => true,
            'meta' => [
                'variants' => ['thumbnail' => 'media/photo-thumbnail.webp'],
            ],
        ]);

        $url = $media->getVariantUrl('thumbnail');

        $this->assertStringContainsString('media/photo-thumbnail.webp', $url);
    }

    // -------------------------------------------------------------------------
    // Accessors — width / height from meta
    // -------------------------------------------------------------------------

    public function test_width_and_height_accessors_read_from_meta(): void
    {
        Storage::fake('public');

        $media = $this->makeMedia([
            'meta' => ['width' => 1920, 'height' => 1080],
        ]);

        $this->assertSame(1920, $media->width);
        $this->assertSame(1080, $media->height);
    }

    public function test_width_and_height_accessors_return_null_when_meta_absent(): void
    {
        Storage::fake('public');

        $media = $this->makeMedia(['meta' => []]);

        $this->assertNull($media->width);
        $this->assertNull($media->height);
    }

    public function test_dimensions_attribute_formats_width_by_height(): void
    {
        Storage::fake('public');

        $media = $this->makeMedia([
            'meta' => ['width' => 800, 'height' => 600],
        ]);

        $this->assertSame('800 × 600', $media->dimensions);
    }

    public function test_dimensions_attribute_returns_null_when_meta_absent(): void
    {
        Storage::fake('public');

        $media = $this->makeMedia(['meta' => []]);

        $this->assertNull($media->dimensions);
    }
}
