<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Mockery;
use TallCms\Cms\Filament\Forms\Components\MediaLibraryFileAttachmentProvider;
use TallCms\Cms\Jobs\OptimizeMediaJob;
use TallCms\Cms\Models\TallcmsMedia;
use TallCms\Cms\Tests\TestCase;

class MediaLibraryFileAttachmentProviderTest extends TestCase
{
    use RefreshDatabase;

    protected MediaLibraryFileAttachmentProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = MediaLibraryFileAttachmentProvider::make();
    }

    public function test_save_creates_media_record(): void
    {
        Storage::fake('public');
        Bus::fake();

        $file = $this->mockTemporaryUploadedFile('test-image.jpg', 'image/jpeg');

        $id = $this->provider->saveUploadedFileAttachment($file);

        $this->assertIsInt($id);
        $media = TallcmsMedia::find($id);
        $this->assertNotNull($media);
        $this->assertEquals('test-image', $media->name);
        $this->assertEquals('test-image.jpg', $media->file_name);
        $this->assertEquals('image/jpeg', $media->mime_type);
        $this->assertEquals('public', $media->disk);
        $this->assertStringStartsWith('cms/rich-editor-attachments/', $media->path);
        $this->assertEquals('Test Image', $media->alt_text);
    }

    public function test_save_skips_alt_text_for_hash_filenames(): void
    {
        Storage::fake('public');
        Bus::fake();

        $file = $this->mockTemporaryUploadedFile('559945921-4f8b4e82-d949-42cd-ac69-52c234e8d977.png', 'image/png');

        $id = $this->provider->saveUploadedFileAttachment($file);

        $media = TallcmsMedia::find($id);
        $this->assertNull($media->alt_text);
    }

    public function test_save_dispatches_optimization_job(): void
    {
        Storage::fake('public');
        Bus::fake();

        $file = $this->mockTemporaryUploadedFile('photo.png', 'image/png');

        $this->provider->saveUploadedFileAttachment($file);

        Bus::assertDispatched(OptimizeMediaJob::class);
    }

    public function test_save_skips_optimization_when_disabled(): void
    {
        Storage::fake('public');
        Bus::fake();
        config(['tallcms.media.optimization.enabled' => false]);

        $file = $this->mockTemporaryUploadedFile('photo.png', 'image/png');

        $this->provider->saveUploadedFileAttachment($file);

        Bus::assertNotDispatched(OptimizeMediaJob::class);
    }

    public function test_get_url_resolves_numeric_media_id(): void
    {
        Storage::fake('public');

        $media = TallcmsMedia::create([
            'name' => 'test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'cms/test.jpg',
            'disk' => 'public',
            'size' => 1024,
        ]);

        $url = $this->provider->getFileAttachmentUrl($media->id);

        $this->assertNotNull($url);
        $this->assertStringContainsString('cms/test.jpg', $url);
    }

    public function test_get_url_falls_back_to_raw_path_on_cms_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cms/attachments/old-file.jpg', 'content');

        $url = $this->provider->getFileAttachmentUrl('cms/attachments/old-file.jpg');

        $this->assertNotNull($url);
        $this->assertStringContainsString('cms/attachments/old-file.jpg', $url);
    }

    public function test_get_url_falls_back_to_filament_default_disk(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        config(['filament.default_filesystem_disk' => 'local']);

        // File only exists on Filament's default disk, not on cms_media_disk
        Storage::disk('local')->put('cms/attachments/legacy.jpg', 'content');

        $url = $this->provider->getFileAttachmentUrl('cms/attachments/legacy.jpg');

        $this->assertNotNull($url);
        $this->assertStringContainsString('cms/attachments/legacy.jpg', $url);
    }

    public function test_get_url_returns_null_for_missing(): void
    {
        $url = $this->provider->getFileAttachmentUrl(99999);

        $this->assertNull($url);
    }

    public function test_get_url_returns_null_when_storage_throws(): void
    {
        Storage::shouldReceive('disk')
            ->andThrow(new \RuntimeException('S3 connection timeout'));

        $url = $this->provider->getFileAttachmentUrl('cms/attachments/some-file.jpg');

        $this->assertNull($url);
    }

    public function test_cleanup_is_noop(): void
    {
        Storage::fake('public');

        $media = TallcmsMedia::create([
            'name' => 'test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'cms/test.jpg',
            'disk' => 'public',
            'size' => 1024,
        ]);

        $this->provider->cleanUpFileAttachments([]);

        $this->assertNotNull(TallcmsMedia::find($media->id));
    }

    public function test_existing_record_not_required(): void
    {
        $this->assertFalse($this->provider->isExistingRecordRequiredToSaveNewFileAttachments());
    }

    public function test_default_visibility(): void
    {
        $this->assertEquals('public', $this->provider->getDefaultFileAttachmentVisibility());
    }

    public function test_sync_alt_text_from_content(): void
    {
        Storage::fake('public');

        $media = TallcmsMedia::create([
            'name' => 'test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'cms/test.jpg',
            'disk' => 'public',
            'size' => 1024,
            'alt_text' => null,
        ]);

        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'image',
                    'attrs' => [
                        'id' => $media->id,
                        'src' => 'http://example.com/test.jpg',
                        'alt' => 'A beautiful sunset',
                    ],
                ],
            ],
        ];

        MediaLibraryFileAttachmentProvider::syncAltTextFromContent($content);

        $this->assertEquals('A beautiful sunset', $media->fresh()->alt_text);
    }

    public function test_sync_alt_text_from_html_content(): void
    {
        Storage::fake('public');

        $media = TallcmsMedia::create([
            'name' => 'test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'cms/test.jpg',
            'disk' => 'public',
            'size' => 1024,
            'alt_text' => 'Auto Generated Name',
        ]);

        $html = '<p>Some text</p><img src="http://example.com/test.jpg" alt="User provided alt" data-id="'.$media->id.'">';

        MediaLibraryFileAttachmentProvider::syncAltTextFromContent($html);

        $this->assertEquals('User provided alt', $media->fresh()->alt_text);
    }

    public function test_sync_alt_text_skips_non_numeric_ids(): void
    {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'image',
                    'attrs' => [
                        'id' => 'some-uuid-string',
                        'src' => 'http://example.com/test.jpg',
                        'alt' => 'Should not cause errors',
                    ],
                ],
            ],
        ];

        // Should not throw
        MediaLibraryFileAttachmentProvider::syncAltTextFromContent($content);
        $this->assertTrue(true);
    }

    /**
     * Create a mock TemporaryUploadedFile for testing.
     */
    protected function mockTemporaryUploadedFile(string $name, string $mimeType): TemporaryUploadedFile
    {
        // Create a real temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'test');

        if (str_starts_with($mimeType, 'image/')) {
            $image = imagecreatetruecolor(100, 80);
            imagejpeg($image, $tempPath);
            imagedestroy($image);
        } else {
            file_put_contents($tempPath, 'test content');
        }

        $mock = Mockery::mock(TemporaryUploadedFile::class);
        $mock->shouldReceive('getClientOriginalName')->andReturn($name);
        $mock->shouldReceive('getMimeType')->andReturn($mimeType);
        $mock->shouldReceive('getSize')->andReturn(filesize($tempPath));
        $mock->shouldReceive('getRealPath')->andReturn($tempPath);
        $mock->shouldReceive('hashName')->andReturn('hashed-'.$name);
        $mock->shouldReceive('storeAs')
            ->andReturnUsing(function ($directory, $fileName, $options) use ($tempPath) {
                $disk = $options['disk'] ?? 'public';
                $path = $directory.'/'.$fileName;
                Storage::disk($disk)->put($path, file_get_contents($tempPath));

                return $path;
            });

        return $mock;
    }
}
