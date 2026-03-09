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

        // Auto-generate alt text from filename, but skip hash/UUID-style names
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $looksLikeHash = preg_match('/^[0-9a-f\-]{16,}$/i', $baseName);
        $altText = ($isImage && ! $looksLikeHash) ? Str::headline($baseName) : null;

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
        if ($isImage && config('tallcms.media.optimization.enabled', true)) {
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
            try {
                $cmsDisk = cms_media_disk();
                if (Storage::disk($cmsDisk)->exists($file)) {
                    return Storage::disk($cmsDisk)->url($file);
                }

                $filamentDisk = config('filament.default_filesystem_disk');
                if ($filamentDisk && $filamentDisk !== $cmsDisk && Storage::disk($filamentDisk)->exists($file)) {
                    return Storage::disk($filamentDisk)->url($file);
                }
            } catch (\Throwable) {
                // Storage connectivity issues (S3 timeout, etc.) — fall through to null
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

    /**
     * Sync alt text from TipTap image nodes to TallcmsMedia records.
     *
     * Filament's FileAttachmentProvider contract doesn't pass alt text during upload,
     * so we walk the saved content and update media records with user-provided alt text.
     */
    public static function syncAltTextFromContent(mixed $content): void
    {
        if (blank($content)) {
            return;
        }

        $updates = [];

        if (is_string($content)) {
            // Try JSON first (TipTap document)
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                static::collectImageAltTextFromTipTap($decoded, $updates);
            } else {
                // HTML content — extract alt and data-id from img tags
                static::collectImageAltTextFromHtml($content, $updates);
            }
        } elseif (is_array($content)) {
            static::collectImageAltTextFromTipTap($content, $updates);
        }

        foreach ($updates as $id => $alt) {
            TallcmsMedia::where('id', $id)->update(['alt_text' => $alt]);
        }
    }

    /**
     * Recursively walk TipTap nodes and collect image alt text keyed by media ID.
     */
    protected static function collectImageAltTextFromTipTap(array $node, array &$updates): void
    {
        if (($node['type'] ?? null) === 'image') {
            $id = $node['attrs']['id'] ?? null;
            $alt = $node['attrs']['alt'] ?? null;

            if (is_numeric($id) && filled($alt)) {
                $updates[(int) $id] = $alt;
            }
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                static::collectImageAltTextFromTipTap($child, $updates);
            }
        }
    }

    /**
     * Extract alt text and media IDs from HTML img tags.
     */
    protected static function collectImageAltTextFromHtml(string $html, array &$updates): void
    {
        // Match <img> tags with both data-id and alt attributes (in any order)
        if (! preg_match_all('/<img\s[^>]*>/i', $html, $matches)) {
            return;
        }

        foreach ($matches[0] as $imgTag) {
            $id = null;
            $alt = null;

            if (preg_match('/data-id=["\'](\d+)["\']/', $imgTag, $m)) {
                $id = (int) $m[1];
            }
            if (preg_match('/\balt=["\']([^"\']*)["\']/', $imgTag, $m)) {
                $alt = $m[1];
            }

            if ($id && filled($alt)) {
                $updates[$id] = html_entity_decode($alt, ENT_QUOTES, 'UTF-8');
            }
        }
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
