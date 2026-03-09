<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Forms\Components;

use Filament\Forms\Components\RichEditor\FileAttachmentProviders\Contracts\FileAttachmentProvider;
use Filament\Forms\Components\RichEditor\RichContentAttribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use TallCms\Cms\Jobs\OptimizeMediaJob;
use TallCms\Cms\Models\TallcmsMedia;

class MediaLibraryFileAttachmentProvider implements FileAttachmentProvider
{
    protected ?RichContentAttribute $attribute = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function attribute(RichContentAttribute $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function saveUploadedFileAttachment(TemporaryUploadedFile $file): mixed
    {
        $disk = cms_media_disk();
        $visibility = cms_media_visibility();
        $originalName = $file->getClientOriginalName();

        $path = $file->storeAs(
            'cms/rich-editor-attachments',
            $file->hashName(),
            ['disk' => $disk, 'visibility' => $visibility],
        );

        // Get image dimensions if applicable
        $meta = [];
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $isImage = str_starts_with($mimeType, 'image/');

        if ($isImage) {
            try {
                $dimensions = getimagesize($file->getRealPath());
                if ($dimensions) {
                    $meta['width'] = $dimensions[0];
                    $meta['height'] = $dimensions[1];
                }
            } catch (\Throwable) {
                // Ignore dimension detection failures
            }
        }

        // Auto-generate alt text from filename
        $altText = $isImage ? Str::headline(pathinfo($originalName, PATHINFO_FILENAME)) : null;

        $media = TallcmsMedia::create([
            'name' => pathinfo($originalName, PATHINFO_FILENAME),
            'file_name' => $originalName,
            'mime_type' => $mimeType,
            'path' => $path,
            'disk' => $disk,
            'size' => $file->getSize(),
            'meta' => $meta,
            'alt_text' => $altText,
        ]);

        // Dispatch optimization job for images when enabled
        if ($isImage && config('tallcms.media.optimization_enabled', true)) {
            OptimizeMediaJob::dispatch($media);
        }

        return $media->id;
    }

    public function getFileAttachmentUrl(mixed $file): ?string
    {
        // Numeric ID — resolve from media library
        if (is_numeric($file)) {
            $media = TallcmsMedia::find($file);

            return $media?->url;
        }

        // String path (legacy) — try cms_media_disk first, then Filament default disk
        if (is_string($file) && $file !== '') {
            $cmsDisk = cms_media_disk();
            if (Storage::disk($cmsDisk)->exists($file)) {
                return Storage::disk($cmsDisk)->url($file);
            }

            $filamentDisk = config('filament.default_filesystem_disk');
            if ($filamentDisk && $filamentDisk !== $cmsDisk && Storage::disk($filamentDisk)->exists($file)) {
                return Storage::disk($filamentDisk)->url($file);
            }
        }

        return null;
    }

    /**
     * No-op for v1. Cross-record media sharing makes per-record cleanup unsafe.
     *
     * @param  array<mixed>  $exceptIds
     */
    public function cleanUpFileAttachments(array $exceptIds): void
    {
        // Intentionally empty — future: tallcms:cleanup-orphaned-media command
    }

    public function getDefaultFileAttachmentVisibility(): ?string
    {
        return cms_media_visibility();
    }

    public function isExistingRecordRequiredToSaveNewFileAttachments(): bool
    {
        return false;
    }
}
