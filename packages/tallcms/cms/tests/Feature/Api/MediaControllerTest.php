<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use TallCms\Cms\Models\TallcmsMedia;
use TallCms\Cms\Policies\TallcmsMediaPolicy;
use TallCms\Cms\Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['tallcms.api.enabled' => true]);
    }

    protected function createUser(): object
    {
        $userModel = config('tallcms.plugin_mode.user_model', 'App\\Models\\User');

        return $userModel::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    protected function actingAsMediaUser(): void
    {
        Sanctum::actingAs($this->createUser(), ['*']);

        $this->mock(TallcmsMediaPolicy::class, function ($mock) {
            $mock->shouldReceive('viewAny')->andReturn(true);
            $mock->shouldReceive('view')->andReturn(true);
            $mock->shouldReceive('create')->andReturn(true);
            $mock->shouldReceive('update')->andReturn(true);
            $mock->shouldReceive('delete')->andReturn(true);
        });
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_uploads_file_to_configured_media_disk(): void
    {
        Storage::fake('custom-disk');
        config(['tallcms.media.disk' => 'custom-disk']);
        $this->actingAsMediaUser();

        $response = $this->postJson('/api/v1/tallcms/media', [
            'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $media = TallcmsMedia::findOrFail($response->json('data.id'));
        $this->assertSame('custom-disk', $media->disk);
        Storage::disk('custom-disk')->assertExists($media->path);
    }

    public function test_store_falls_back_to_auto_detected_disk_when_not_configured(): void
    {
        Storage::fake('public');
        config(['tallcms.media.disk' => null, 'filesystems.default' => 'public']);
        $this->actingAsMediaUser();

        $response = $this->postJson('/api/v1/tallcms/media', [
            'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $this->assertSame('public', TallcmsMedia::findOrFail($response->json('data.id'))->disk);
    }

    public function test_store_ignores_disk_parameter_from_request(): void
    {
        Storage::fake('public');
        config(['tallcms.media.disk' => null, 'filesystems.default' => 'public']);
        $this->actingAsMediaUser();

        $response = $this->postJson('/api/v1/tallcms/media', [
            'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
            'disk' => 'local', // must be ignored
        ]);

        $response->assertStatus(201);
        $this->assertSame('public', TallcmsMedia::findOrFail($response->json('data.id'))->disk);
    }

    public function test_store_saves_image_dimensions_in_meta(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        $response = $this->postJson('/api/v1/tallcms/media', [
            'file' => UploadedFile::fake()->image('photo.jpg', 640, 480),
        ]);

        $response->assertStatus(201);
        $media = TallcmsMedia::findOrFail($response->json('data.id'));
        $this->assertSame(640, $media->meta['width']);
        $this->assertSame(480, $media->meta['height']);
    }

    public function test_store_dimensions_are_accessible_via_accessors(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        $response = $this->postJson('/api/v1/tallcms/media', [
            'file' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ]);

        $response->assertStatus(201);
        $media = TallcmsMedia::findOrFail($response->json('data.id'));
        $this->assertSame(800, $media->width);
        $this->assertSame(600, $media->height);
    }

    public function test_store_does_not_set_dimensions_for_non_image(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        $response = $this->postJson('/api/v1/tallcms/media', [
            'file' => UploadedFile::fake()->create('document.pdf', 200, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $media = TallcmsMedia::findOrFail($response->json('data.id'));
        $this->assertEmpty($media->meta);
        $this->assertNull($media->width);
        $this->assertNull($media->height);
    }

    public function test_store_creates_record_with_correct_metadata(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        $response = $this->postJson('/api/v1/tallcms/media', [
            'file' => UploadedFile::fake()->create('brochure.pdf', 512, 'application/pdf'),
            'name' => 'Company Brochure',
            'alt_text' => 'Our company overview',
            'caption' => 'Version 2024',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Company Brochure')
            ->assertJsonPath('data.mime_type', 'application/pdf')
            ->assertJsonPath('data.alt_text', 'Our company overview')
            ->assertJsonPath('data.caption', 'Version 2024');

        $this->assertDatabaseHas('tallcms_media', [
            'name' => 'Company Brochure',
            'alt_text' => 'Our company overview',
            'caption' => 'Version 2024',
        ]);
    }

    public function test_store_uses_original_filename_as_name_when_not_provided(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        $response = $this->postJson('/api/v1/tallcms/media', [
            'file' => UploadedFile::fake()->create('my-report.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'my-report.pdf');
    }

    public function test_store_handles_unreadable_image_gracefully(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        // A file claiming to be JPEG but with invalid image content
        $response = $this->postJson('/api/v1/tallcms/media', [
            'file' => UploadedFile::fake()->create('corrupt.jpg', 10, 'image/jpeg'),
        ]);

        $response->assertStatus(201);
        $media = TallcmsMedia::findOrFail($response->json('data.id'));
        $this->assertEmpty($media->meta); // dimensions skipped gracefully, no 500
    }

    public function test_store_requires_authentication(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/v1/tallcms/media', [
            'file' => UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf'),
        ]);

        $response->assertStatus(401);
    }

    public function test_store_validates_file_is_required(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        $response = $this->postJson('/api/v1/tallcms/media', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_file_from_model_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/test.pdf', 'content');
        $this->actingAsMediaUser();

        $media = TallcmsMedia::create([
            'name' => 'test',
            'file_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'path' => 'media/test.pdf',
            'disk' => 'public',
            'size' => 7,
        ]);

        $this->deleteJson("/api/v1/tallcms/media/{$media->id}")
            ->assertStatus(200);

        Storage::disk('public')->assertMissing('media/test.pdf');
        $this->assertDatabaseMissing('tallcms_media', ['id' => $media->id]);
    }

    public function test_destroy_deletes_file_from_custom_disk(): void
    {
        Storage::fake('cms-media');
        Storage::disk('cms-media')->put('media/file.pdf', 'content');
        $this->actingAsMediaUser();

        $media = TallcmsMedia::create([
            'name' => 'file',
            'file_name' => 'file.pdf',
            'mime_type' => 'application/pdf',
            'path' => 'media/file.pdf',
            'disk' => 'cms-media',
            'size' => 7,
        ]);

        $this->deleteJson("/api/v1/tallcms/media/{$media->id}")
            ->assertStatus(200);

        Storage::disk('cms-media')->assertMissing('media/file.pdf');
    }

    public function test_destroy_deletes_variants_via_model_hook(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/photo.jpg', 'original');
        Storage::disk('public')->put('media/photo-thumbnail.webp', 'thumb');
        Storage::disk('public')->put('media/photo-medium.webp', 'medium');
        $this->actingAsMediaUser();

        $media = TallcmsMedia::create([
            'name' => 'photo',
            'file_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/photo.jpg',
            'disk' => 'public',
            'size' => 1000,
            'has_variants' => true,
            'meta' => [
                'variants' => [
                    'thumbnail' => 'media/photo-thumbnail.webp',
                    'medium' => 'media/photo-medium.webp',
                ],
            ],
        ]);

        $this->deleteJson("/api/v1/tallcms/media/{$media->id}")
            ->assertStatus(200);

        Storage::disk('public')->assertMissing('media/photo.jpg');
        Storage::disk('public')->assertMissing('media/photo-thumbnail.webp');
        Storage::disk('public')->assertMissing('media/photo-medium.webp');
    }

    public function test_destroy_requires_authentication(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/file.pdf', 'content');

        $media = TallcmsMedia::create([
            'name' => 'file',
            'file_name' => 'file.pdf',
            'mime_type' => 'application/pdf',
            'path' => 'media/file.pdf',
            'disk' => 'public',
            'size' => 7,
        ]);

        $this->deleteJson("/api/v1/tallcms/media/{$media->id}")
            ->assertStatus(401);
    }

    public function test_destroy_returns_404_for_missing_media(): void
    {
        $this->actingAsMediaUser();

        $this->deleteJson('/api/v1/tallcms/media/99999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // index — has_variants filter
    // -------------------------------------------------------------------------

    public function test_index_filter_has_variants_true_returns_only_optimized_items(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        $withVariants = TallcmsMedia::create([
            'name' => 'optimized',
            'file_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/photo.jpg',
            'disk' => 'public',
            'size' => 1000,
            'has_variants' => true,
            'meta' => ['variants' => ['thumbnail' => 'media/photo-thumbnail.webp']],
        ]);

        TallcmsMedia::create([
            'name' => 'raw',
            'file_name' => 'raw.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/raw.jpg',
            'disk' => 'public',
            'size' => 1000,
            'has_variants' => false,
        ]);

        $response = $this->getJson('/api/v1/tallcms/media?filter[has_variants]=true');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($withVariants->id));
        $this->assertCount(1, $ids);
    }

    public function test_index_filter_has_variants_false_excludes_optimized_items(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        TallcmsMedia::create([
            'name' => 'optimized',
            'file_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/photo.jpg',
            'disk' => 'public',
            'size' => 1000,
            'has_variants' => true,
            'meta' => ['variants' => ['thumbnail' => 'media/photo-thumbnail.webp']],
        ]);

        $raw = TallcmsMedia::create([
            'name' => 'raw',
            'file_name' => 'raw.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/raw.jpg',
            'disk' => 'public',
            'size' => 1000,
            'has_variants' => false,
        ]);

        $response = $this->getJson('/api/v1/tallcms/media?filter[has_variants]=false');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($raw->id));
        $this->assertCount(1, $ids);
    }

    // -------------------------------------------------------------------------
    // show — resource includes variants from meta
    // -------------------------------------------------------------------------

    public function test_show_resource_includes_variants_when_meta_variants_populated(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        $media = TallcmsMedia::create([
            'name' => 'photo',
            'file_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/photo.jpg',
            'disk' => 'public',
            'size' => 1000,
            'has_variants' => true,
            'meta' => [
                'variants' => [
                    'thumbnail' => 'media/photo-thumbnail.webp',
                    'medium' => 'media/photo-medium.webp',
                ],
            ],
        ]);

        $this->getJson("/api/v1/tallcms/media/{$media->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.variants.thumbnail', 'media/photo-thumbnail.webp')
            ->assertJsonPath('data.variants.medium', 'media/photo-medium.webp');
    }

    public function test_show_resource_omits_variants_key_when_none_exist(): void
    {
        Storage::fake('public');
        $this->actingAsMediaUser();

        $media = TallcmsMedia::create([
            'name' => 'raw',
            'file_name' => 'raw.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/raw.jpg',
            'disk' => 'public',
            'size' => 1000,
            'has_variants' => false,
        ]);

        $response = $this->getJson("/api/v1/tallcms/media/{$media->id}");
        $response->assertStatus(200);
        $this->assertArrayNotHasKey('variants', $response->json('data'));
    }
}
